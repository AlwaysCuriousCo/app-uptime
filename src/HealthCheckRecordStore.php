<?php

namespace AlwaysCurious\AppUptime;

use AlwaysCurious\AppUptime\Events\HealthCheckRecovered;
use AlwaysCurious\AppUptime\Models\HealthCheckRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Health\Checks\Result;
use Spatie\Health\Enums\Status;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResults;

/**
 * A spatie/laravel-health {@see ResultStore} that persists each probe as a
 * normalised {@see HealthCheckRecord} row.
 *
 * This is the seam between Spatie's runner and this package's `/up` pages:
 * Spatie runs the checks and hands us a {@see Result} per check, and we
 * flatten each one into the columns the heartbeat bars, uptime stats and
 * response-time chart query back — keeping `response_time_ms` a first-class
 * column rather than buried in a JSON blob.
 *
 * Recovery (failed → ok) is announced by dispatching a {@see HealthCheckRecovered}
 * event so the consuming app can decide how to alert (Slack, email, statuspage)
 * without coupling the store to any specific notification.
 */
final class HealthCheckRecordStore implements ResultStore
{
    /**
     * Persist one {@see HealthCheckRecord} per check in a completed run, and
     * dispatch a {@see HealthCheckRecovered} event for any check that has
     * just come back up.
     *
     * @param  Collection<int, Result>  $checkResults
     */
    public function save(Collection $checkResults): void
    {
        foreach ($checkResults as $result) {
            $key = $result->check->getName();
            $healthy = $result->status === Status::ok();

            // Capture the prior state before inserting this run's row.
            $previous = HealthCheckRecord::query()
                ->forCheck($key)
                ->latest('checked_at')
                ->first();

            $record = HealthCheckRecord::create([
                'check_key' => $key,
                'healthy' => $healthy,
                'message' => Str::limit($result->getShortSummary(), 252),
                'response_time_ms' => $this->responseTime($result),
                'checked_at' => $result->ended_at ?? now(),
            ]);

            // A failing → passing transition is a recovery worth announcing.
            if ($healthy && $previous !== null && ! $previous->healthy) {
                $this->dispatchRecovered($result->check->getLabel(), $record);
            }
        }
    }

    /**
     * The `/up` pages read history straight from {@see HealthCheckRecord} via
     * {@see HealthHistory}, so Spatie's own results page is never rendered and
     * this accessor is unused — required only by the {@see ResultStore}
     * contract.
     */
    public function latestResults(): ?StoredCheckResults
    {
        return null;
    }

    /**
     * Dispatch the {@see HealthCheckRecovered} event for a check that just
     * recovered, bounding the outage window with the last good probe.
     */
    private function dispatchRecovered(string $label, HealthCheckRecord $record): void
    {
        // The last good probe before this one bounds the outage window.
        $lastHealthy = HealthCheckRecord::query()
            ->forCheck($record->check_key)
            ->where('healthy', true)
            ->whereKeyNot($record->getKey())
            ->latest('checked_at')
            ->first();

        $downtime = $lastHealthy
            ? $lastHealthy->checked_at->diffForHumans($record->checked_at, true)
            : null;

        HealthCheckRecovered::dispatch(
            $record->check_key,
            $label,
            $record,
            $downtime,
        );
    }

    /**
     * Pull the probe round-trip time a check reported in its result metadata.
     */
    private function responseTime(Result $result): ?int
    {
        $ms = $result->meta['response_time_ms'] ?? null;

        return $ms === null ? null : (int) $ms;
    }
}
