<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CheckMetaTokenHealth;
use App\Jobs\SyncAds;
use App\Jobs\SyncInsights;
use App\Models\AdAccount;
use App\Models\DataDeletionRequest;
use App\Models\SocialAccount;
use App\Services\ConsentService;
use App\Services\Meta\MetaConnectionService;
use App\Services\Meta\SignedRequest;
use App\Support\Role;
use App\Support\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MetaController extends Controller
{
    public function consent(Request $request, ConsentService $consents)
    {
        $workspace = $request->attributes->get('workspace');
        $current = $consents->current('meta');
        $active = $consents->activeForUser($workspace->id, $request->user()->id, 'meta');

        return response()->json($current + ['granted' => $active !== null]);
    }

    public function connect(Request $request, MetaConnectionService $meta)
    {
        $this->assertConnect($request);
        $data = $request->validate([
            'consent' => ['required', 'accepted'],
            'text_version' => ['required', 'string', 'max:64'],
        ], [
            'consent.required' => 'Açık rıza gerekli.',
            'consent.accepted' => 'Açık rıza gerekli.',
            'text_version.required' => 'Açık rıza metni sürümü gerekli.',
        ]);

        return response()->json([
            'url' => $meta->start(
                $request->attributes->get('workspace'),
                $request->user(),
                true,
                $data['text_version'],
            ),
        ]);
    }

    public function withdraw(Request $request, MetaConnectionService $meta)
    {
        $this->assertConnect($request);
        $meta->withdraw($request->attributes->get('workspace'), $request->user());

        return response()->json(['ok' => true]);
    }

    public function callback(Request $request, MetaConnectionService $meta)
    {
        $request->validate(['code' => ['required', 'string'], 'state' => ['required', 'string']]);
        $result = DB::transaction(function () use ($request, $meta) {
            $cached = \Illuminate\Support\Facades\Cache::get('meta.connect.'.hash('sha256', $request->string('state')));
            if (is_array($cached)) {
                app(WorkspaceContext::class)->activate((int) $cached['workspace_id']);
            }

            return $meta->handleCallback($request->string('code'), $request->string('state'));
        });

        return redirect(rtrim((string) config('sinsocial.frontend_url'), '/').'/uygulama/hesaplar?connected=1');
    }

    public function systemUser(Request $request, MetaConnectionService $meta)
    {
        $data = $request->validate([
            'access_token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:120'],
        ]);
        $account = $meta->connectSystemUser($request->attributes->get('workspace'), $request->user(), $data['access_token'], $data['name']);

        return response()->json(['account' => $account->toPublicArray()]);
    }

    public function select(Request $request, MetaConnectionService $meta)
    {
        $this->assertConnect($request);
        $data = $request->validate([
            'page_ids' => ['array'],
            'page_ids.*' => ['string'],
            'ig_ids' => ['array'],
            'ig_ids.*' => ['string'],
            'ad_account_ids' => ['array'],
            'ad_account_ids.*' => ['string'],
        ]);
        $created = $meta->selectAssets(
            $request->attributes->get('workspace'),
            $request->user(),
            $data['page_ids'] ?? [],
            $data['ig_ids'] ?? [],
            $data['ad_account_ids'] ?? [],
        );

        return response()->json(['accounts' => $created]);
    }

    public function accounts(Request $request)
    {
        return response()->json([
            'accounts' => SocialAccount::query()->orderBy('name')->get()->map->toPublicArray(),
        ]);
    }

    public function sync(Request $request, ConsentService $consents)
    {
        $workspace = $request->attributes->get('workspace');
        if (! $consents->allowsSync($workspace->id)) {
            return response()->json(['ok' => true, 'paused' => true]);
        }

        SocialAccount::query()->whereIn('platform', ['facebook_page', 'instagram'])->each(function (SocialAccount $account) use ($workspace) {
            SyncInsights::dispatch($account->id, $workspace->id);
            CheckMetaTokenHealth::dispatch($account->id, $workspace->id);
        });
        AdAccount::query()->each(fn (AdAccount $account) => SyncAds::dispatch($account->id, $workspace->id));

        return response()->json(['ok' => true, 'paused' => false]);
    }

    public function dataDeletion(Request $request, SignedRequest $parser)
    {
        return DB::transaction(function () use ($request, $parser) {
            try {
                $data = $parser->parse((string) $request->input('signed_request'));
            } catch (\Throwable) {
                abort(400, 'signed_request doğrulanamadı.');
            }

            app(WorkspaceContext::class)->activate(null, true);
            $userId = (string) ($data['user_id'] ?? '');
            SocialAccount::query()->withoutGlobalScopes()
                ->where(function ($query) use ($userId) {
                    $query->where('external_id', $userId)->orWhere('parent_external_id', $userId);
                })
                ->update([
                    'status' => 'disconnected',
                    'access_token' => null,
                ]);

            $code = Str::lower(Str::random(16));
            DataDeletionRequest::query()->create([
                'confirmation_code' => $code,
                'status' => 'completed',
                'external_user_id' => $userId,
                'payload' => ['user_id' => $userId],
            ]);

            $url = rtrim((string) config('app.url'), '/').'/veri-silme/'.$code;

            return response()->json(['url' => $url, 'confirmation_code' => $code]);
        });
    }

    public function deletionStatus(string $code)
    {
        $row = DataDeletionRequest::query()->where('confirmation_code', $code)->firstOrFail();

        return response()->json([
            'confirmation_code' => $row->confirmation_code,
            'status' => $row->status,
        ]);
    }

    public function deauthorize(Request $request, SignedRequest $parser)
    {
        return DB::transaction(function () use ($request, $parser) {
            try {
                $data = $parser->parse((string) $request->input('signed_request'));
            } catch (\Throwable) {
                abort(400, 'signed_request doğrulanamadı.');
            }
            app(WorkspaceContext::class)->activate(null, true);
            $userId = (string) ($data['user_id'] ?? '');
            SocialAccount::query()->withoutGlobalScopes()
                ->where(function ($query) use ($userId) {
                    $query->where('external_id', $userId)->orWhere('parent_external_id', $userId);
                })
                ->update(['status' => 'disconnected', 'access_token' => null]);

            return response()->json(['ok' => true]);
        });
    }

    private function assertConnect(Request $request): void
    {
        if (! Role::canManageTeam($request->attributes->get('membership')->role)) {
            abort(403);
        }
    }
}
