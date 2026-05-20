<?php

use AlwaysCurious\AppUptime\Events\HealthCheckRecovered;
use AlwaysCurious\AppUptime\HealthCheckRecordStore;
use AlwaysCurious\AppUptime\Models\HealthCheckRecord;
use AlwaysCurious\AppUptime\Tests\Support\StubCheck;
use Illuminate\Support\Facades\Event;
use Spatie\Health\Checks\Result;
use Spatie\Health\Facades\Health;

beforeEach(function (): void {
    StubCheck::$healthy = true;
    Health::clearChecks();
    Health::checks([StubCheck::new()]);
});

it('persists one record per check result with summary, healthy flag and response time', function (): void {
    $check = StubCheck::new();
    $result = Result::make()
        ->ok()
        ->shortSummary('Reachable')
        ->meta(['response_time_ms' => 87]);
    $result->check = $check;
    $result->ended_at = now();

    app(HealthCheckRecordStore::class)->save(collect([$result]));

    $record = HealthCheckRecord::query()->forCheck('stub')->first();

    expect($record)->not->toBeNull()
        ->and($record->check_key)->toBe('stub')
        ->and($record->healthy)->toBeTrue()
        ->and($record->message)->toBe('Reachable')
        ->and($record->response_time_ms)->toBe(87);
});

it('records a failure when the result status is not ok', function (): void {
    $check = StubCheck::new();
    $result = Result::make()
        ->failed('Upstream down')
        ->shortSummary('Upstream down')
        ->meta(['response_time_ms' => 999]);
    $result->check = $check;
    $result->ended_at = now();

    app(HealthCheckRecordStore::class)->save(collect([$result]));

    $record = HealthCheckRecord::query()->forCheck('stub')->first();

    expect($record->healthy)->toBeFalse()
        ->and($record->message)->toBe('Upstream down');
});

it('dispatches HealthCheckRecovered on a failed → ok transition', function (): void {
    Event::fake([HealthCheckRecovered::class]);

    // Seed an earlier healthy record so downtime is computable, then a more
    // recent failure so the next store call becomes the up-edge.
    HealthCheckRecord::create([
        'check_key' => 'stub',
        'healthy' => true,
        'message' => 'Was up',
        'response_time_ms' => 50,
        'checked_at' => now()->subMinutes(5),
    ]);
    HealthCheckRecord::create([
        'check_key' => 'stub',
        'healthy' => false,
        'message' => 'Was failing',
        'response_time_ms' => 120,
        'checked_at' => now()->subMinute(),
    ]);

    $check = StubCheck::new();
    $result = Result::make()
        ->ok()
        ->shortSummary('Back up')
        ->meta(['response_time_ms' => 42]);
    $result->check = $check;
    $result->ended_at = now();

    app(HealthCheckRecordStore::class)->save(collect([$result]));

    Event::assertDispatched(HealthCheckRecovered::class, function (HealthCheckRecovered $event): bool {
        return $event->checkKey === 'stub'
            && $event->checkLabel === 'Stub Check'
            && $event->record->healthy === true
            && $event->downtime !== null;
    });
});

it('does not dispatch HealthCheckRecovered when the previous probe was already healthy', function (): void {
    Event::fake([HealthCheckRecovered::class]);

    HealthCheckRecord::create([
        'check_key' => 'stub',
        'healthy' => true,
        'message' => 'Still up',
        'response_time_ms' => 50,
        'checked_at' => now()->subMinute(),
    ]);

    $check = StubCheck::new();
    $result = Result::make()->ok()->shortSummary('Still up')->meta(['response_time_ms' => 42]);
    $result->check = $check;
    $result->ended_at = now();

    app(HealthCheckRecordStore::class)->save(collect([$result]));

    Event::assertNotDispatched(HealthCheckRecovered::class);
});
