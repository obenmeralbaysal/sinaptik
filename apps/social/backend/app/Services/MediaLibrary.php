<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\User;
use App\Models\Workspace;
use App\Support\WorkspaceContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MediaLibrary
{
    public function __construct(private LimitGuard $limits) {}

    public function store(Workspace $workspace, User $user, UploadedFile $file, ?int $brandId = null): MediaAsset
    {
        $bytes = (int) $file->getSize();
        $limitGb = $this->limits->effective($workspace, 'storage_gb');
        if ($limitGb !== null) {
            $used = (int) MediaAsset::query()->where('workspace_id', $workspace->id)->sum('size_bytes');
            if (($used + $bytes) / 1_000_000_000 > $limitGb) {
                throw new \App\Exceptions\LimitExceeded('storage_gb', $limitGb);
            }
        }

        $disk = (string) config('sinsocial.media_disk');
        $path = $workspace->id.'/'.Str::uuid()->toString().'.'.$file->guessExtension();
        Storage::disk($disk)->put($path, $file->get());

        return MediaAsset::query()->create([
            'workspace_id' => $workspace->id,
            'brand_id' => $brandId,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size_bytes' => $bytes,
            'uploaded_by' => $user->id,
        ]);
    }

    public function signedUrl(MediaAsset $asset): string
    {
        if ($asset->disk === 's3' || $asset->disk === 'r2') {
            return Storage::disk($asset->disk)->temporaryUrl(
                $asset->path,
                now()->addMinutes((int) config('sinsocial.media_url_ttl_minutes')),
            );
        }

        return URL::temporarySignedRoute(
            'media.public',
            now()->addMinutes((int) config('sinsocial.media_url_ttl_minutes')),
            ['media' => $asset->id],
        );
    }

    public function stream(MediaAsset $asset)
    {
        app(WorkspaceContext::class)->allowMedia($asset->id);

        return Storage::disk($asset->disk)->response($asset->path, $asset->original_name);
    }
}
