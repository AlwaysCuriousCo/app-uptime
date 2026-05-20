<?php

namespace AlwaysCurious\AppUptime;

use AlwaysCurious\AppUptime\Models\HealthCheckRecord;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Reads persisted {@see HealthCheckRecord} rows back into the heartbeat bars,
 * uptime percentages and response-time series shown on the `/up` pages.
 */
final class HealthHistory
{
    /**
     * The most recent probe results for a check, oldest first so they render
     * left-to-right as a heartbeat bar.
     *
     * @return Collection<int, HealthCheckRecord>
     */
    public function heartbeats(string $key, int $limit): Collection
    {
        return HealthCheckRecord::query()
            ->forCheck($key)
            ->latest('checked_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * The single most recent probe result for a check, if any exist.
     */
    public function latest(string $key): ?HealthCheckRecord
    {
        return HealthCheckRecord::query()
            ->forCheck($key)
            ->latest('checked_at')
            ->first();
    }

    /**
     * Percentage of probes that passed since the given time, or null when no
     * probes were recorded in that window.
     */
    public function uptime(string $key, DateTimeInterface $since): ?float
    {
        $row = HealthCheckRecord::query()
            ->forCheck($key)
            ->where('checked_at', '>=', $since)
            ->selectRaw('COUNT(*) as total, SUM(healthy) as up')
            ->first();

        $total = (int) ($row->total ?? 0);

        if ($total === 0) {
            return null;
        }

        return round((int) ($row->up ?? 0) / $total * 100, 2);
    }

    /**
     * Mean probe response time (ms) since the given time, or null when no
     * timed probes were recorded in that window.
     */
    public function averageResponseTime(string $key, DateTimeInterface $since): ?int
    {
        $average = HealthCheckRecord::query()
            ->forCheck($key)
            ->where('checked_at', '>=', $since)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');

        return $average === null ? null : (int) round((float) $average);
    }
}
