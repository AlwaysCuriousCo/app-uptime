<?php

use AlwaysCurious\AppUptime\HealthHistory;
use AlwaysCurious\AppUptime\Models\HealthCheckRecord;

it('returns heartbeats oldest first for the heartbeat bar', function (): void {
    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => true,  'message' => 'a', 'response_time_ms' => 10, 'checked_at' => now()->subMinutes(3)]);
    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => false, 'message' => 'b', 'response_time_ms' => 20, 'checked_at' => now()->subMinutes(2)]);
    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => true,  'message' => 'c', 'response_time_ms' => 30, 'checked_at' => now()->subMinute()]);

    $history = app(HealthHistory::class);

    $beats = $history->heartbeats('stub', 10);

    expect($beats)->toHaveCount(3)
        ->and($beats->first()->message)->toBe('a')
        ->and($beats->last()->message)->toBe('c');
});

it('returns the latest record', function (): void {
    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => true,  'message' => 'old', 'response_time_ms' => 10, 'checked_at' => now()->subMinutes(2)]);
    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => false, 'message' => 'new', 'response_time_ms' => 20, 'checked_at' => now()->subMinute()]);

    expect(app(HealthHistory::class)->latest('stub')->message)->toBe('new');
});

it('computes uptime percentage rounded to two decimals', function (): void {
    foreach (range(1, 3) as $i) {
        HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => true, 'message' => 'ok', 'response_time_ms' => 10, 'checked_at' => now()->subMinutes($i)]);
    }
    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => false, 'message' => 'fail', 'response_time_ms' => 10, 'checked_at' => now()->subMinute()]);

    // 3 healthy of 4 total = 75.00
    expect(app(HealthHistory::class)->uptime('stub', now()->subHour()))->toBe(75.0);
});

it('returns null uptime when no probes recorded in the window', function (): void {
    expect(app(HealthHistory::class)->uptime('stub', now()->subHour()))->toBeNull();
});

it('averages response time skipping rows with no recorded ms', function (): void {
    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => true, 'message' => 'a', 'response_time_ms' => 100, 'checked_at' => now()->subMinutes(2)]);
    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => true, 'message' => 'b', 'response_time_ms' => 300, 'checked_at' => now()->subMinute()]);
    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => true, 'message' => 'c', 'response_time_ms' => null, 'checked_at' => now()]);

    expect(app(HealthHistory::class)->averageResponseTime('stub', now()->subHour()))->toBe(200);
});

it('prunes records older than the configured retention', function (): void {
    config()->set('uptime.retention_days', 30);

    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => true, 'message' => 'old', 'response_time_ms' => 10, 'checked_at' => now()->subDays(60)]);
    HealthCheckRecord::create(['check_key' => 'stub', 'healthy' => true, 'message' => 'new', 'response_time_ms' => 10, 'checked_at' => now()->subDay()]);

    HealthCheckRecord::first()->prunable()->delete();

    expect(HealthCheckRecord::count())->toBe(1)
        ->and(HealthCheckRecord::first()->message)->toBe('new');
});
