<?php

use App\Jobs\FetchXmlSource;
use App\Jobs\GenerateRoutinePosts;
use App\Jobs\ProcessTargetReplies;
use App\Jobs\SendScheduledPost;
use App\Models\Post;
use App\Models\Routine;
use App\Models\SocialAccount;
use App\Models\XmlSource;
use App\Support\RoutineSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schedule;

// Dispatch due posts every minute
Schedule::call(function () {
    Post::withoutGlobalScopes()
        ->where('status', 'pending')
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', now())
        ->pluck('id')
        ->each(fn ($id) => SendScheduledPost::dispatch($id));
})->everyMinute()->name('dispatch-due-posts')->withoutOverlapping();

// Process target replies for each active X account every 5 minutes
Schedule::call(function () {
    SocialAccount::withoutGlobalScopes()
        ->where('provider', 'x')
        ->where('is_active', true)
        ->pluck('id')
        ->each(fn ($id) => ProcessTargetReplies::dispatch($id));
})->everyFiveMinutes()->name('dispatch-replies')->withoutOverlapping();

// Fetch XML sources every 15 minutes
Schedule::call(function () {
    XmlSource::withoutGlobalScopes()
        ->where('is_active', true)
        ->where(function ($q) {
            $q->whereNull('last_fetched_at')
              ->orWhereRaw('last_fetched_at + (fetch_interval_minutes || \' minutes\')::interval <= now()');
        })
        ->pluck('id')
        ->each(fn ($id) => FetchXmlSource::dispatch($id));
})->everyFifteenMinutes()->name('dispatch-xml-sources')->withoutOverlapping();

// Queue the next routine run once its Istanbul publish window is still ahead.
Schedule::call(function () {
    Routine::withoutGlobalScopes()
        ->where('is_active', true)
        ->get()
        ->each(function (Routine $routine) {
            $schedule = RoutineSchedule::fromRoutine($routine);
            if ($routine->schedule_anchor) {
                $today = CarbonImmutable::now($schedule->timezone)->startOfDay();
                if (! $schedule->isSlotDay($today) || ! $schedule->hasRemainingWindow($today)) {
                    return;
                }
            }

            GenerateRoutinePosts::dispatch($routine->id);
        });
})->everyFifteenMinutes()->name('generate-routine-posts')->withoutOverlapping();
