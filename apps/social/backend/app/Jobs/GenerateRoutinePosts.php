<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Routine;
use App\Services\AI\AIManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateRoutinePosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $routineId) {}

    public function handle(AIManager $aiManager): void
    {
        /** @var Routine|null $routine */
        $routine = Routine::withoutGlobalScopes()->with('organization')->find($this->routineId);
        if (! $routine || ! $routine->is_active || ! $routine->organization) return;

        try {
            $ai = $aiManager->forOrganization($routine->organization);
        } catch (\Throwable $e) {
            \Log::warning("GenerateRoutinePosts: AI not configured for org {$routine->organization_id}: ".$e->getMessage());
            return;
        }

        $count = $routine->daily_count;
        $items = $ai->generateBatchPosts([
            'persona' => $routine->persona,
            'tone' => $routine->tone,
            'language' => $routine->language,
            'topic' => $routine->topic,
        ], $count, $routine->ai_model);

        if (empty($items)) return;

        $interval = max(1, (int) floor((24 * 60) / $count));

        foreach ($items as $i => $text) {
            $imageUrl = null;
            if ($routine->generate_images) {
                try {
                    $imageUrl = $ai->generateImage($text, $routine->image_model);
                } catch (\Throwable $e) {
                    $imageUrl = null;
                }
            }

            Post::create([
                'organization_id' => $routine->organization_id,
                'social_account_id' => $routine->social_account_id,
                'routine_id' => $routine->id,
                'content' => $text,
                'media_paths' => $imageUrl ? [$imageUrl] : null,
                'scheduled_at' => now()->addMinutes($interval * $i),
                'status' => 'pending',
            ]);
        }
    }
}
