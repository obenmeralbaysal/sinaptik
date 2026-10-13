<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\Routine;
use App\Services\AI\AIManager;
use App\Support\RoutineSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateRoutinePosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $routineId) {}

    public function handle(AIManager $aiManager): void
    {
        /** @var Routine|null $routine */
        $routine = Routine::withoutGlobalScopes()->with('organization')->find($this->routineId);
        if (! $routine || ! $routine->is_active || ! $routine->organization) {
            return;
        }

        $lock = Cache::lock('routine-generate:'.$routine->id, 600);
        if (! $lock->get()) {
            return;
        }

        try {
            $this->generate($routine, $aiManager);
        } finally {
            $lock->release();
        }
    }

    private function generate(Routine $routine, AIManager $aiManager): void
    {
        $now = CarbonImmutable::now();
        $schedule = RoutineSchedule::fromRoutine($routine);

        if (! $schedule->windowIsValid()) {
            return;
        }

        if (! $routine->schedule_anchor) {
            $first = $schedule->firstDate($now);
            if (! $first) {
                return;
            }
            $routine->schedule_anchor = $first->toDateString();
            $routine->save();
            $schedule = RoutineSchedule::fromRoutine($routine);
        }

        $today = $now->timezone($schedule->timezone)->startOfDay();
        if (! $schedule->isSlotDay($today) || ! $schedule->hasRemainingWindow($today, $now)) {
            return;
        }

        $existing = Post::withoutGlobalScopes()
            ->where('routine_id', $routine->id)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [
                $today->utc()->format('Y-m-d H:i:s'),
                $today->endOfDay()->utc()->format('Y-m-d H:i:s'),
            ])
            ->count();

        if ($existing > 0) {
            return;
        }

        try {
            $ai = $aiManager->forOrganization($routine->organization);
        } catch (\Throwable $e) {
            Log::warning("GenerateRoutinePosts: AI not configured for org {$routine->organization_id}: ".$e->getMessage());

            return;
        }

        $count = max(1, (int) $routine->daily_count);
        $items = $ai->generateBatchPosts([
            'persona' => $routine->persona,
            'tone' => $routine->tone,
            'language' => $routine->language,
            'topic' => $routine->topic,
        ], $count, $routine->ai_model);

        if (empty($items)) {
            return;
        }

        $prepared = [];
        foreach ($items as $text) {
            $imageUrl = null;
            if ($routine->generate_images) {
                try {
                    $imageUrl = $ai->generateImage($text, $routine->image_model);
                } catch (\Throwable $e) {
                    $imageUrl = null;
                }
            }
            $prepared[] = ['text' => $text, 'image' => $imageUrl];
        }

        $now = CarbonImmutable::now();
        $today = $now->timezone($schedule->timezone)->startOfDay();
        if (! $schedule->hasRemainingWindow($today, $now)) {
            Log::warning("GenerateRoutinePosts: publish window closed before routine {$routine->id} could be scheduled.");

            return;
        }

        $alreadyQueued = Post::withoutGlobalScopes()
            ->where('routine_id', $routine->id)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [
                $today->utc()->format('Y-m-d H:i:s'),
                $today->endOfDay()->utc()->format('Y-m-d H:i:s'),
            ])
            ->exists();

        if ($alreadyQueued) {
            return;
        }

        $instants = $schedule->randomInstants($today, count($prepared), $now);
        if (count($instants) < count($prepared)) {
            Log::warning("GenerateRoutinePosts: publish window closed before routine {$routine->id} could be scheduled.");

            return;
        }

        foreach ($prepared as $i => $item) {
            Post::create([
                'organization_id' => $routine->organization_id,
                'social_account_id' => $routine->social_account_id,
                'routine_id' => $routine->id,
                'content' => $item['text'],
                'media_paths' => $item['image'] ? [$item['image']] : null,
                'scheduled_at' => $instants[$i],
                'status' => 'pending',
            ]);
        }
    }
}
