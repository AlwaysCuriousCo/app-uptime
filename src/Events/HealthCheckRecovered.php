<?php

namespace AlwaysCurious\AppUptime\Events;

use AlwaysCurious\AppUptime\Models\HealthCheckRecord;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired the moment a check transitions from failing to passing — the up-edge
 * of an outage.
 *
 * spatie/laravel-health only notifies on failure; this event is the recovery
 * half. Listen for it in your app to send a "back to normal" Slack ping, page
 * an on-call, post to a status page, etc. The package itself ships no listener
 * for it — the wiring is intentionally yours.
 *
 * `$downtime` is the human-readable duration the check was failing, derived
 * from the last healthy record. It is null when no prior healthy probe exists
 * (the check has never passed before this run).
 */
final class HealthCheckRecovered
{
    use Dispatchable;

    public function __construct(
        public readonly string $checkKey,
        public readonly string $checkLabel,
        public readonly HealthCheckRecord $record,
        public readonly ?string $downtime,
    ) {}
}
