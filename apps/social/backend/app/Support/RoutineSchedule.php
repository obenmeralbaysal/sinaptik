<?php

namespace App\Support;

use App\Models\Routine;
use Carbon\CarbonImmutable;

class RoutineSchedule
{
    public const TIMEZONE = 'Europe/Istanbul';

    public readonly int $frequencyDays;

    public readonly string $windowStart;

    public readonly string $windowEnd;

    public readonly string $timezone;

    public readonly ?string $anchorDate;

    public function __construct(
        int $frequencyDays,
        string $windowStart,
        string $windowEnd,
        string $timezone = self::TIMEZONE,
        ?string $anchorDate = null,
    ) {
        $this->frequencyDays = max(1, $frequencyDays);
        $this->windowStart = self::normalizeTime($windowStart);
        $this->windowEnd = self::normalizeTime($windowEnd);
        $this->timezone = $timezone !== '' ? $timezone : self::TIMEZONE;
        $this->anchorDate = $anchorDate !== null && $anchorDate !== '' ? substr($anchorDate, 0, 10) : null;
    }

    public static function fromRoutine(Routine $routine): self
    {
        $rawAnchor = $routine->getRawOriginal('schedule_anchor');

        return new self(
            (int) ($routine->frequency_days ?: 1),
            (string) ($routine->window_start ?: '09:00'),
            (string) ($routine->window_end ?: '10:00'),
            (string) ($routine->timezone ?: self::TIMEZONE),
            $rawAnchor ? (string) $rawAnchor : null,
        );
    }

    public function windowIsValid(): bool
    {
        return $this->windowEnd > $this->windowStart;
    }

    public function firstDate(?CarbonImmutable $now = null): ?CarbonImmutable
    {
        return $this->upcomingDates(1, $now)[0] ?? null;
    }

    /**
     * @return list<CarbonImmutable> Local start-of-day for each slot.
     */
    public function upcomingDates(int $count, ?CarbonImmutable $now = null): array
    {
        if ($count < 1) {
            return [];
        }

        $now = $this->localNow($now);
        $freq = $this->frequencyDays;
        $cursor = $now->startOfDay();

        if ($this->anchorDate !== null) {
            $anchor = CarbonImmutable::parse($this->anchorDate, $this->timezone)->startOfDay();
            if ($cursor->lt($anchor)) {
                $cursor = $anchor;
            } else {
                $diff = $this->dayDiff($anchor, $cursor);
                $mod = $diff % $freq;
                if ($mod !== 0) {
                    $cursor = $cursor->addDays($freq - $mod);
                }
            }
        }

        if (! $this->hasRemainingWindow($cursor, $now)) {
            $cursor = $cursor->addDays($this->anchorDate !== null ? $freq : 1);
        }

        $dates = [];
        for ($i = 0; $i < $count; $i++) {
            $dates[] = $cursor;
            $cursor = $cursor->addDays($freq);
        }

        return $dates;
    }

    public function isSlotDay(CarbonImmutable $localDate): bool
    {
        if ($this->anchorDate === null) {
            return false;
        }

        $anchor = CarbonImmutable::parse($this->anchorDate, $this->timezone)->startOfDay();
        $day = $localDate->timezone($this->timezone)->startOfDay();
        $diff = $this->dayDiff($anchor, $day);

        return $diff >= 0 && $diff % $this->frequencyDays === 0;
    }

    public function hasRemainingWindow(CarbonImmutable $localDate, ?CarbonImmutable $now = null): bool
    {
        $now = $this->localNow($now);
        $end = $this->atTime($localDate, $this->windowEnd);

        return $now->lt($end->subMinute());
    }

    /**
     * @return list<CarbonImmutable> UTC instants, one per post, grouped by slot.
     */
    public function instantsForPosts(int $postCount, int $perSlot, ?CarbonImmutable $now = null): array
    {
        if ($postCount < 1) {
            return [];
        }

        $perSlot = max(1, $perSlot);
        $dates = $this->upcomingDates((int) ceil($postCount / $perSlot), $now);
        $instants = [];

        foreach ($dates as $index => $date) {
            $need = min($perSlot, $postCount - ($index * $perSlot));
            if ($need < 1) {
                break;
            }

            $picked = $this->randomInstants($date, $need, $now);
            if (count($picked) < $need) {
                break;
            }

            array_push($instants, ...$picked);
        }

        return $instants;
    }

    /**
     * @return list<CarbonImmutable> UTC instants inside the local window.
     */
    public function randomInstants(CarbonImmutable $localDate, int $count, ?CarbonImmutable $now = null): array
    {
        if ($count < 1) {
            return [];
        }

        $now = $this->localNow($now);
        $start = $this->atTime($localDate, $this->windowStart);
        $end = $this->atTime($localDate, $this->windowEnd);

        if ($now->gt($start)) {
            $start = $now->addMinute()->second(0);
        }

        if ($start->gte($end)) {
            return [];
        }

        $span = (int) floor($start->diffInSeconds($end) / 60);
        if ($span < 1) {
            return [];
        }

        $offsets = range(0, $span - 1);
        shuffle($offsets);

        $instants = [];
        foreach (array_slice($offsets, 0, min($count, $span)) as $offset) {
            $instants[] = $start->addMinutes($offset)->utc();
        }

        while (count($instants) < $count) {
            $instants[] = $start->addMinutes(random_int(0, $span - 1))->utc();
        }

        return $instants;
    }

    private function localNow(?CarbonImmutable $now): CarbonImmutable
    {
        return ($now ?? CarbonImmutable::now())->timezone($this->timezone);
    }

    private function atTime(CarbonImmutable $localDate, string $hm): CarbonImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $hm));
        $local = $localDate->timezone($this->timezone);

        return $local->setTime($hour, $minute, 0);
    }

    private function dayDiff(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $start = $from->timezone($this->timezone)->startOfDay()->getTimestamp();
        $end = $to->timezone($this->timezone)->startOfDay()->getTimestamp();

        return (int) round(($end - $start) / 86400);
    }

    private static function normalizeTime(string $time): string
    {
        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        return '09:00';
    }
}
