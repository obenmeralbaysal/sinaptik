<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\CheckMetaTokenHealth;
use App\Jobs\SyncAds;
use App\Jobs\SyncInsights;
use App\Models\AdAccount;
use App\Models\DataDeletionRequest;
use App\Models\SocialAccount;
use App\Services\Meta\MetaConnectionService;
use App\Services\Meta\SignedRequest;
use App\Support\Role;
use App\Support\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MetaController extends Controller
{
    public function connect(Request $request, MetaConnectionService $meta)
    {
        $this->assertConnect($request);

        return response()->json(['url' => $meta->start($request->attributes->get('workspace'), $request->user())]);
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

    public function sync(Request $request)
    {
        $workspace = $request->attributes->get('workspace');
        SocialAccount::query()->whereIn('platform', ['facebook_page', 'instagram'])->each(function (SocialAccount $account) use ($workspace) {
            SyncInsights::dispatch($account->id, $workspace->id);
            CheckMetaTokenHealth::dispatch($account->id, $workspace->id);
        });
        AdAccount::query()->each(fn (AdAccount $account) => SyncAds::dispatch($account->id, $workspace->id));

        return response()->json(['ok' => true]);
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
