const TIMEZONE = "Europe/Istanbul";

type Day = { year: number; month: number; day: number };

function istanbulClock(date = new Date()): Day & { minutes: number } {
  const parts = Object.fromEntries(
    new Intl.DateTimeFormat("en-GB", {
      timeZone: TIMEZONE,
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      hourCycle: "h23",
    })
      .formatToParts(date)
      .map((part) => [part.type, part.value]),
  );

  return {
    year: Number(parts.year),
    month: Number(parts.month),
    day: Number(parts.day),
    minutes: Number(parts.hour) * 60 + Number(parts.minute),
  };
}

function addDays(day: Day, amount: number): Day {
  const next = new Date(Date.UTC(day.year, day.month - 1, day.day));
  next.setUTCDate(next.getUTCDate() + amount);
  return { year: next.getUTCFullYear(), month: next.getUTCMonth() + 1, day: next.getUTCDate() };
}

function formatDay(day: Day): string {
  return new Intl.DateTimeFormat("en-GB", {
    timeZone: TIMEZONE,
    weekday: "short",
    day: "numeric",
    month: "short",
  }).format(new Date(Date.UTC(day.year, day.month - 1, day.day, 12)));
}

function minutes(value: string): number {
  const [hour, minute] = value.split(":").map(Number);
  return hour * 60 + minute;
}

export function previewSlotLabels(options: {
  count: number;
  perSlot: number;
  frequencyDays: number;
  windowStart: string;
  windowEnd: string;
}): string[] {
  const now = istanbulClock();
  const end = minutes(options.windowEnd);
  let cursor: Day = { year: now.year, month: now.month, day: now.day };
  if (now.minutes >= end - 1) {
    cursor = addDays(cursor, 1);
  }

  const frequency = Math.max(1, options.frequencyDays);
  const perSlot = Math.max(1, options.perSlot);
  const labels: string[] = [];

  for (let index = 0; index < options.count; index++) {
    if (index > 0 && index % perSlot === 0) {
      cursor = addDays(cursor, frequency);
    }
    labels.push(`${formatDay(cursor)} · ${options.windowStart}–${options.windowEnd}`);
  }

  return labels;
}

export function routineCadenceLabel(routine: {
  frequency_days?: number;
  window_start?: string | null;
  window_end?: string | null;
  daily_count?: number;
}): string {
  const days = routine.frequency_days || 1;
  const cadence = days === 1 ? "Every day" : `Every ${days} days`;
  const start = (routine.window_start || "09:00").slice(0, 5);
  const end = (routine.window_end || "10:00").slice(0, 5);
  const posts = routine.daily_count || 1;
  const volume = posts === 1 ? "1 post" : `${posts} posts`;

  return `${cadence} · ${start}–${end} Istanbul · ${volume}`;
}
