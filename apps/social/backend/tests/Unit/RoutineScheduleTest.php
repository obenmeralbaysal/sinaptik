<?php

namespace Tests\Unit;

use App\Support\RoutineSchedule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class RoutineScheduleTest extends TestCase
{
    public function test_open_window_starts_today_and_steps_by_frequency(): void
    {
        $now = CarbonImmutable::parse('2026-10-14 08:00:00', 'Europe/Istanbul');
        $schedule = new RoutineSchedule(3, '09:00', '10:00');

        $dates = $schedule->upcomingDates(3, $now);

        $this->assertSame(
            ['2026-10-14', '2026-10-17', '2026-10-20'],
            array_map(fn (CarbonImmutable $date) => $date->toDateString(), $dates),
        );
    }

    public function test_closed_window_starts_the_next_day(): void
    {
        $now = CarbonImmutable::parse('2026-10-14 10:00:00', 'Europe/Istanbul');
        $schedule = new RoutineSchedule(3, '09:00', '10:00');

        $dates = $schedule->upcomingDates(2, $now);

        $this->assertSame(
            ['2026-10-15', '2026-10-18'],
            array_map(fn (CarbonImmutable $date) => $date->toDateString(), $dates),
        );
    }

    public function test_anchor_skips_days_that_are_not_on_the_cadence(): void
    {
        $now = CarbonImmutable::parse('2026-10-16 08:00:00', 'Europe/Istanbul');
        $schedule = new RoutineSchedule(3, '09:00', '10:00', 'Europe/Istanbul', '2026-10-14');

        $this->assertSame('2026-10-17', $schedule->firstDate($now)?->toDateString());
        $this->assertFalse($schedule->isSlotDay($now->startOfDay()));
        $this->assertTrue($schedule->isSlotDay(CarbonImmutable::parse('2026-10-17', 'Europe/Istanbul')));
    }

    public function test_random_instant_stays_inside_the_istanbul_window(): void
    {
        $now = CarbonImmutable::parse('2026-10-14 08:00:00', 'Europe/Istanbul');
        $day = $now->startOfDay();
        $schedule = new RoutineSchedule(3, '09:00', '10:00');

        $instants = $schedule->randomInstants($day, 12, $now);

        $this->assertCount(12, $instants);
        $this->assertCount(12, array_unique(array_map(fn (CarbonImmutable $instant) => $instant->getTimestamp(), $instants)));

        foreach ($instants as $instant) {
            $local = $instant->timezone('Europe/Istanbul');
            $minutes = $local->hour * 60 + $local->minute;
            $this->assertSame('2026-10-14', $local->toDateString());
            $this->assertGreaterThanOrEqual(9 * 60, $minutes);
            $this->assertLessThan(10 * 60, $minutes);
        }
    }

    public function test_random_instant_does_not_land_in_the_past(): void
    {
        $now = CarbonImmutable::parse('2026-10-14 09:30:00', 'Europe/Istanbul');
        $schedule = new RoutineSchedule(1, '09:00', '10:00');

        foreach ($schedule->randomInstants($now->startOfDay(), 8, $now) as $instant) {
            $local = $instant->timezone('Europe/Istanbul');
            $minutes = $local->hour * 60 + $local->minute;
            $this->assertGreaterThanOrEqual(9 * 60 + 31, $minutes);
            $this->assertLessThan(10 * 60, $minutes);
        }
    }

    public function test_posts_are_spread_across_slots(): void
    {
        $now = CarbonImmutable::parse('2026-10-14 08:00:00', 'Europe/Istanbul');
        $schedule = new RoutineSchedule(3, '09:00', '10:00');

        $instants = $schedule->instantsForPosts(4, 1, $now);
        $days = array_map(
            fn (CarbonImmutable $instant) => $instant->timezone('Europe/Istanbul')->toDateString(),
            $instants,
        );

        $this->assertSame(['2026-10-14', '2026-10-17', '2026-10-20', '2026-10-23'], $days);
    }
}
