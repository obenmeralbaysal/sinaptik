<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Routine;
use App\Services\AI\AIManager;
use App\Services\X\XClient;
use App\Support\RoutineSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoutineController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Routine::query()->with('socialAccount:id,username,provider')->latest()->get(),
        ]);
    }

    public function show(Routine $routine): JsonResponse
    {
        return response()->json(['data' => $routine]);
    }

    public function generate(Request $request, AIManager $aiManager): JsonResponse
    {
        $data = $request->validate([
            'persona' => ['nullable', 'string', 'max:200'],
            'tone' => ['nullable', 'string', 'max:30'],
            'language' => ['nullable', 'string', 'max:10'],
            'topic' => ['required', 'string', 'max:300'],
            'count' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'model' => ['nullable', 'string'],
        ]);

        $count = $data['count'] ?? 5;
        $items = $aiManager->forCurrentOrganization()
            ->generateBatchPosts($data, $count, $data['model'] ?? null);

        return response()->json(['data' => $items]);
    }

    public function regenerateOne(Request $request, AIManager $aiManager): JsonResponse
    {
        $data = $request->validate([
            'persona' => ['nullable', 'string', 'max:200'],
            'tone' => ['nullable', 'string', 'max:30'],
            'language' => ['nullable', 'string', 'max:10'],
            'topic' => ['required', 'string', 'max:300'],
            'model' => ['nullable', 'string'],
        ]);

        $items = $aiManager->forCurrentOrganization()
            ->generateBatchPosts($data, 1, $data['model'] ?? null);
        return response()->json(['data' => $items[0] ?? null]);
    }

    public function store(Request $request, XClient $x): JsonResponse
    {
        $data = $request->validate([
            'social_account_id' => ['required', Rule::exists('social_accounts', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'persona' => ['nullable', 'string', 'max:200'],
            'tone' => ['nullable', 'string', 'max:30'],
            'language' => ['nullable', 'string', 'max:10'],
            'topic' => ['required', 'string', 'max:300'],
            'ai_model' => ['nullable', 'string'],
            'image_model' => ['nullable', 'string'],
            'generate_images' => ['sometimes', 'boolean'],
            'daily_count' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'frequency_days' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'window_start' => ['sometimes', 'date_format:H:i'],
            'window_end' => ['sometimes', 'date_format:H:i'],
            'schedule_cron' => ['nullable', 'string', 'max:50'],
            'posts' => ['required', 'array', 'min:1'],
            'posts.*.content' => ['required', 'string', 'max:280'],
            'posts.*.media_url' => ['nullable', 'string'],
            'posts.*.scheduled_at' => ['nullable', 'date'],
            'delivery' => ['sometimes', Rule::in(['draft', 'publish', 'schedule'])],
        ]);

        $schedule = new RoutineSchedule(
            (int) ($data['frequency_days'] ?? 1),
            $data['window_start'] ?? '09:00',
            $data['window_end'] ?? '10:00',
        );
        $this->assertWindow($schedule);

        $delivery = $data['delivery'] ?? 'draft';
        $perSlot = max(1, (int) ($data['daily_count'] ?? 1));
        $instants = [];
        if ($delivery === 'schedule') {
            $instants = $schedule->instantsForPosts(count($data['posts']), $perSlot);
            if (count($instants) < count($data['posts'])) {
                throw ValidationException::withMessages([
                    'window_end' => 'The publish window does not leave enough time to schedule these posts.',
                ]);
            }
        }

        $routine = DB::transaction(function () use ($data, $schedule, $delivery, $perSlot, $instants) {
            $routine = Routine::create([
                'social_account_id' => $data['social_account_id'],
                'name' => $data['name'],
                'persona' => $data['persona'] ?? null,
                'tone' => $data['tone'] ?? 'supportive',
                'language' => $data['language'] ?? 'en',
                'topic' => $data['topic'],
                'ai_model' => $data['ai_model'] ?? null,
                'image_model' => $data['image_model'] ?? null,
                'generate_images' => $data['generate_images'] ?? false,
                'daily_count' => $perSlot,
                'frequency_days' => $schedule->frequencyDays,
                'window_start' => $schedule->windowStart,
                'window_end' => $schedule->windowEnd,
                'timezone' => RoutineSchedule::TIMEZONE,
                'schedule_anchor' => $schedule->firstDate()?->toDateString(),
                'schedule_cron' => $data['schedule_cron'] ?? null,
            ]);

            foreach ($data['posts'] as $i => $p) {
                $scheduledAt = $delivery === 'schedule' ? $instants[$i] : null;
                Post::create([
                    'social_account_id' => $data['social_account_id'],
                    'routine_id' => $routine->id,
                    'content' => $p['content'],
                    'media_paths' => isset($p['media_url']) ? [$p['media_url']] : null,
                    'scheduled_at' => $scheduledAt,
                    'status' => $scheduledAt ? 'pending' : 'draft',
                ]);
            }

            return $routine;
        });

        $routine->load('posts.socialAccount');

        if (($data['delivery'] ?? 'draft') === 'publish') {
            foreach ($routine->posts as $post) {
                try {
                    $result = $x->postContent($post->socialAccount, $post->content);
                    $post->update([
                        'status' => 'posted',
                        'posted_at' => now(),
                        'provider_post_id' => $result['id'] ?? null,
                        'error' => null,
                    ]);
                } catch (\Throwable $e) {
                    $post->update(['status' => 'failed', 'error' => $e->getMessage()]);
                }
            }
        }

        return response()->json(['data' => $routine->load('posts')], 201);
    }

    public function update(Request $request, Routine $routine): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
            'schedule_cron' => ['nullable', 'string', 'max:50'],
            'daily_count' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'frequency_days' => ['sometimes', 'integer', 'min:1', 'max:30'],
            'window_start' => ['sometimes', 'date_format:H:i'],
            'window_end' => ['sometimes', 'date_format:H:i'],
        ]);

        $schedule = new RoutineSchedule(
            (int) ($data['frequency_days'] ?? $routine->frequency_days ?? 1),
            $data['window_start'] ?? $routine->window_start ?? '09:00',
            $data['window_end'] ?? $routine->window_end ?? '10:00',
            $routine->timezone ?: RoutineSchedule::TIMEZONE,
            $routine->getRawOriginal('schedule_anchor'),
        );
        $this->assertWindow($schedule);

        $routine->update([
            ...$data,
            'frequency_days' => $schedule->frequencyDays,
            'window_start' => $schedule->windowStart,
            'window_end' => $schedule->windowEnd,
        ]);
        return response()->json(['data' => $routine]);
    }

    public function destroy(Routine $routine): JsonResponse
    {
        $routine->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    private function assertWindow(RoutineSchedule $schedule): void
    {
        if (! $schedule->windowIsValid()) {
            throw ValidationException::withMessages([
                'window_end' => 'End time must be after the start time.',
            ]);
        }
    }

    public function models(AIManager $aiManager): JsonResponse
    {
        $ai = $aiManager->forCurrentOrganization();
        return response()->json([
            'text' => $ai->listTextModels(),
            'image' => $ai->listImageModels(),
        ]);
    }
}
