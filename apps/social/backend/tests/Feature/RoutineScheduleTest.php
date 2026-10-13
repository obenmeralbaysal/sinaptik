<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutineScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_routine_publishes_inside_the_istanbul_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 08:00:00', 'Europe/Istanbul')->utc());

        try {
            [$user, $account] = $this->workspace();

            $response = $this->actingAs($user)
                ->withHeader('X-Organization-Slug', 'lab')
                ->postJson('/api/routines', [
                    'social_account_id' => $account->id,
                    'name' => 'Morning notes',
                    'topic' => 'quiet product updates',
                    'daily_count' => 1,
                    'frequency_days' => 3,
                    'window_start' => '09:00',
                    'window_end' => '10:00',
                    'delivery' => 'schedule',
                    'posts' => [
                        ['content' => 'First'],
                        ['content' => 'Second'],
                        ['content' => 'Third'],
                    ],
                ]);

            $response->assertCreated();
            $response->assertJsonPath('data.frequency_days', 3);
            $response->assertJsonPath('data.window_start', '09:00');
            $response->assertJsonPath('data.window_end', '10:00');
            $response->assertJsonPath('data.timezone', 'Europe/Istanbul');
            $response->assertJsonPath('data.schedule_anchor', '2026-10-14');

            $posts = Post::query()->orderBy('scheduled_at')->get();
            $this->assertCount(3, $posts);
            $this->assertSame(
                ['2026-10-14', '2026-10-17', '2026-10-20'],
                $posts->map(fn (Post $post) => $post->scheduled_at->timezone('Europe/Istanbul')->toDateString())->all(),
            );

            foreach ($posts as $post) {
                $this->assertSame('pending', $post->status);
                $local = $post->scheduled_at->timezone('Europe/Istanbul');
                $minutes = $local->hour * 60 + $local->minute;
                $this->assertGreaterThanOrEqual(9 * 60, $minutes);
                $this->assertLessThan(10 * 60, $minutes);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_window_end_must_be_after_the_start(): void
    {
        [$user, $account] = $this->workspace();

        $this->actingAs($user)
            ->withHeader('X-Organization-Slug', 'lab')
            ->postJson('/api/routines', [
                'social_account_id' => $account->id,
                'name' => 'Morning notes',
                'topic' => 'quiet product updates',
                'window_start' => '10:00',
                'window_end' => '09:00',
                'delivery' => 'schedule',
                'posts' => [
                    ['content' => 'First'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('window_end');
    }

    /**
     * @return array{0: User, 1: SocialAccount}
     */
    private function workspace(): array
    {
        $user = User::factory()->create();
        $org = Organization::create(['slug' => 'lab', 'name' => 'Lab', 'owner_id' => $user->id]);
        $org->users()->attach($user->id, ['role' => 'owner']);
        $user->forceFill(['current_organization_id' => $org->id])->save();

        $account = SocialAccount::create([
            'organization_id' => $org->id,
            'provider' => 'x',
            'provider_user_id' => '1',
            'username' => 'lab',
        ]);

        return [$user, $account];
    }
}
