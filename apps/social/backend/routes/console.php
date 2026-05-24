<?php

use App\Jobs\FetchXmlSource;
use App\Jobs\GenerateRoutinePosts;
use App\Jobs\ProcessTargetReplies;
use App\Jobs\SendScheduledPost;
use App\Models\Post;
use App\Models\Routine;
use App\Models\SocialAccount;
use App\Models\XmlSource;
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

// Daily routine post generation
Schedule::call(function () {
    Routine::withoutGlobalScopes()
        ->where('is_active', true)
        ->pluck('id')
        ->each(fn ($id) => GenerateRoutinePosts::dispatch($id));
})->dailyAt('08:00')->name('generate-routine-posts')->withoutOverlapping();
