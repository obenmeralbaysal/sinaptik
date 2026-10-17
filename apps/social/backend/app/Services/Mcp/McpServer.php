<?php

namespace App\Services\Mcp;

use App\Models\AdAccount;
use App\Models\AdCampaign;
use App\Models\AdInsight;
use App\Models\AdProposal;
use App\Models\AdSet;
use App\Models\ApiKey;
use App\Models\MediaAsset;
use App\Models\OauthAccessToken;
use App\Models\Post;
use App\Models\PostInsight;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Ads\AdProposalService;
use App\Services\AuditLogger;
use App\Services\LimitGuard;
use App\Services\Publishing\PostService;
use App\Services\UsageMeter;
use App\Support\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class McpServer
{
    public function __construct(
        private LimitGuard $limits,
        private UsageMeter $usage,
        private AuditLogger $audit,
        private PostService $posts,
        private AdProposalService $ads,
    ) {}

    public function handle(Request $request): array
    {
        $actor = $this->authenticate($request);
        $workspace = Workspace::query()->findOrFail($actor['workspace_id']);
        app(WorkspaceContext::class)->activate($workspace->id);
        $this->throttle($workspace, $request);

        $payload = $request->json()->all();
        $method = (string) ($payload['method'] ?? '');
        $id = $payload['id'] ?? null;
        $params = (array) ($payload['params'] ?? []);

        if (str_starts_with($method, 'notifications/')) {
            return ['http' => 202, 'body' => null];
        }

        try {
            $result = match ($method) {
                'initialize' => $this->initialize($params),
                'tools/list' => ['tools' => $this->toolDefinitions()],
                'tools/call' => $this->callTool($workspace, $actor, $params, $request->ip()),
                'ping' => ['pong' => true],
                default => throw new McpException('Bilinmeyen metod.', 400, -32601),
            };
            if ($method !== 'tools/call') {
                $this->audit->log($workspace->id, $actor['type'], $actor['id'], 'mcp.'.$method, 'ok', $method, null, null, null, $request->ip());
            }

            return ['http' => 200, 'body' => ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]];
        } catch (McpException $exception) {
            $this->audit->log($workspace->id, $actor['type'], $actor['id'], 'mcp.'.$method, 'error', $method, $exception->getMessage(), null, null, $request->ip());

            return [
                'http' => $exception->status,
                'body' => [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => ['code' => $exception->codeId, 'message' => $exception->getMessage()],
                ],
            ];
        }
    }

    private function authenticate(Request $request): array
    {
        $header = (string) $request->bearerToken();
        if ($header === '') {
            throw new McpException('Yetkilendirme gerekli.', 401, -32001);
        }

        return DB::transaction(function () use ($request, $header) {
            app(WorkspaceContext::class)->activate(null, true);

            if (str_starts_with($header, 'smk_')) {
                $key = ApiKey::query()->withoutGlobalScopes()->where('key_hash', hash('sha256', $header))->whereNull('revoked_at')->first();
                if (! $key) {
                    throw new McpException('API anahtarı geçersiz.', 401, -32001);
                }
                app(WorkspaceContext::class)->activate($key->workspace_id);
                $key->forceFill(['last_used_at' => now(), 'last_used_ip' => $request->ip()])->save();

                return [
                    'type' => 'api_key',
                    'id' => $key->id,
                    'workspace_id' => $key->workspace_id,
                    'brand_id' => $key->brand_id,
                    'scopes' => $key->scopes ?? [],
                    'user' => null,
                ];
            }

            $token = OauthAccessToken::query()
                ->where('token_hash', hash('sha256', $header))
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->first();
            if (! $token) {
                throw new McpException('OAuth belirteci geçersiz.', 401, -32001);
            }
            app(WorkspaceContext::class)->activate($token->workspace_id);

            return [
                'type' => 'oauth_client',
                'id' => $token->id,
                'workspace_id' => $token->workspace_id,
                'brand_id' => null,
                'scopes' => $token->scopes ?? [],
                'user' => User::query()->find($token->user_id),
            ];
        });
    }

    private function throttle(Workspace $workspace, Request $request): void
    {
        $perMinute = $this->limits->plan($workspace)->limit('mcp_calls_per_minute') ?? 60;
        $bucket = 'mcp:rpm:'.$workspace->id.':'.now()->format('YmdHi');
        $count = (int) Redis::incr($bucket);
        if ($count === 1) {
            Redis::expire($bucket, 70);
        }
        if ($count > $perMinute) {
            throw new McpException('Dakikalık hız limiti aşıldı.', 429, -32029);
        }

        $monthly = $this->limits->effective($workspace, 'mcp_calls_per_month');
        $used = $this->usage->increment($workspace, 'mcp_calls');
        if ($monthly !== null && $used > $monthly) {
            throw new McpException('Aylık API kotası aşıldı.', 429, -32029);
        }
    }

    private function initialize(array $params): array
    {
        return [
            'protocolVersion' => $params['protocolVersion'] ?? '2025-03-26',
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => 'sinapp-social', 'version' => '1.0.0'],
        ];
    }

    private function callTool(Workspace $workspace, array $actor, array $params, ?string $ip): array
    {
        $name = (string) ($params['name'] ?? '');
        $arguments = (array) ($params['arguments'] ?? []);
        $required = $this->scopes()[$name] ?? null;
        if ($required === null) {
            throw new McpException('Bilinmeyen araç.', 404, -32601);
        }
        foreach ($required as $scope) {
            if (! in_array($scope, $actor['scopes'], true)) {
                $this->audit->log($workspace->id, $actor['type'], $actor['id'], 'mcp.tool', 'denied', $name, json_encode($this->summary($arguments)), null, null, $ip);
                throw new McpException('Kapsam yetersiz: '.$scope, 403, -32003);
            }
        }
        if ($actor['brand_id'] && isset($arguments['brand_id']) && (int) $arguments['brand_id'] !== (int) $actor['brand_id']) {
            throw new McpException('Anahtar bu marka ile sınırlı.', 403, -32003);
        }

        $result = $this->dispatchTool($workspace, $actor, $name, $arguments);
        $this->audit->log(
            $workspace->id,
            $actor['type'],
            $actor['id'],
            'mcp.tool',
            'ok',
            $name,
            json_encode($this->summary($arguments)),
            null,
            $result['budget_delta'] ?? null,
            $ip,
        );

        return ['content' => [['type' => 'text', 'text' => json_encode($result, JSON_UNESCAPED_UNICODE)]], 'structured' => $result];
    }

    private function dispatchTool(Workspace $workspace, array $actor, string $name, array $arguments): array
    {
        $user = $actor['user'] instanceof User ? $actor['user'] : User::query()->find($workspace->owner_id);

        return match ($name) {
            'list_accounts' => [
                'accounts' => SocialAccount::query()
                    ->when($actor['brand_id'], fn ($q) => $q->where('brand_id', $actor['brand_id']))
                    ->get()->map->toPublicArray()->values(),
            ],
            'upload_media' => ['error' => 'Medya yükleme HTTP çoklu parçasıyla /api/media üzerinden yapılır. MCP bu sürümde URL kaydı kabul eder.', 'accepted' => false],
            'create_post', 'schedule_post' => $this->createPost($workspace, $user, $name, $arguments),
            'list_posts' => ['posts' => Post::query()->latest('id')->limit(50)->get()],
            'get_post_insights' => ['insights' => PostInsight::query()->where('post_id', $arguments['post_id'] ?? 0)->first()],
            'list_ad_accounts' => ['ad_accounts' => AdAccount::query()->get()],
            'list_campaigns' => ['campaigns' => AdCampaign::query()->limit(100)->get()],
            'get_ad_insights' => ['insights' => AdInsight::query()->latest('metric_date')->limit(30)->get()],
            'propose_optimization', 'update_budget', 'pause_resume' => $this->propose($workspace, $user, $actor, $name, $arguments),
            'apply_optimization' => $this->apply($workspace, $user, (int) ($arguments['proposal_id'] ?? 0)),
            default => throw new McpException('Bilinmeyen araç.'),
        };
    }

    private function createPost(Workspace $workspace, User $user, string $name, array $arguments): array
    {
        $status = $name === 'schedule_post' ? 'scheduled' : ($arguments['status'] ?? 'draft');
        $post = $this->posts->create($workspace, $user, [
            'brand_id' => $arguments['brand_id'] ?? null,
            'social_account_id' => $arguments['social_account_id'] ?? null,
            'body' => $arguments['body'] ?? '',
            'post_type' => $arguments['post_type'] ?? 'feed',
            'media_ids' => $arguments['media_ids'] ?? [],
            'scheduled_at' => $arguments['scheduled_at'] ?? null,
            'status' => $status,
        ]);

        return ['post_id' => $post->id, 'status' => $post->status];
    }

    private function propose(Workspace $workspace, User $user, array $actor, string $name, array $arguments): array
    {
        $adSet = AdSet::query()->findOrFail($arguments['ad_set_id'] ?? 0);
        $kind = $name === 'pause_resume' ? 'status' : 'budget';
        $payload = $kind === 'status'
            ? ['status' => $arguments['status'] ?? 'PAUSED']
            : ['daily_budget_minor' => (int) ($arguments['daily_budget_minor'] ?? 0)];
        $auto = (bool) ($arguments['auto'] ?? false) && $this->ads->autoApplyAllowed($workspace);
        $proposal = $this->ads->propose($workspace, $adSet, $kind, $payload, 'mcp', $user, $actor['type'] === 'api_key' ? $actor['id'] : null, $auto);

        return ['proposal_id' => $proposal->id, 'status' => $proposal->status, 'budget_delta' => $kind === 'budget' ? ((int) $payload['daily_budget_minor'] - (int) $adSet->daily_budget_minor) : 0];
    }

    private function apply(Workspace $workspace, User $user, int $proposalId): array
    {
        $proposal = AdProposal::query()->findOrFail($proposalId);
        if ($proposal->status === 'proposed' && ! $this->ads->autoApplyAllowed($workspace)) {
            throw new McpException('Öneri önce onaylanmalı.', 409);
        }
        $applied = $this->ads->apply($workspace, $proposal, $user, 'mcp');

        return ['proposal_id' => $applied->id, 'status' => $applied->status];
    }

    private function scopes(): array
    {
        return [
            'list_accounts' => ['posts:read'],
            'upload_media' => ['media:write'],
            'create_post' => ['posts:write'],
            'schedule_post' => ['posts:write'],
            'list_posts' => ['posts:read'],
            'get_post_insights' => ['analytics:read'],
            'list_ad_accounts' => ['ads:read'],
            'list_campaigns' => ['ads:read'],
            'get_ad_insights' => ['ads:read'],
            'propose_optimization' => ['ads:write'],
            'apply_optimization' => ['ads:write'],
            'update_budget' => ['ads:budget'],
            'pause_resume' => ['ads:write'],
        ];
    }

    private function toolDefinitions(): array
    {
        return collect($this->scopes())->map(fn ($scopes, $name) => [
            'name' => $name,
            'description' => $name,
            'inputSchema' => ['type' => 'object'],
            'annotations' => ['requiredScopes' => $scopes],
        ])->values()->all();
    }

    private function summary(array $arguments): array
    {
        unset($arguments['access_token'], $arguments['token']);

        return $arguments;
    }
}
