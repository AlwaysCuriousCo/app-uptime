<?php

use AlwaysCurious\AppUptime\Models\HealthCheckRecord;
use AlwaysCurious\AppUptime\Tests\Support\StubCheck;
use Spatie\Health\Facades\Health;

beforeEach(function (): void {
    StubCheck::$healthy = true;
    Health::clearChecks();
    Health::checks([StubCheck::new()]);
});

it('renders /up with each registered check', function (): void {
    HealthCheckRecord::create([
        'check_key' => 'stub',
        'healthy' => true,
        'message' => 'All good',
        'response_time_ms' => 42,
        'checked_at' => now(),
    ]);

    $response = $this->get('/up');

    $response->assertOk()
        ->assertSee('Stub Check')
        ->assertSee('All good')
        ->assertSee('Application up');
});

it('returns 500 when the latest probe for any check failed', function (): void {
    HealthCheckRecord::create([
        'check_key' => 'stub',
        'healthy' => false,
        'message' => 'Down',
        'response_time_ms' => 99,
        'checked_at' => now(),
    ]);

    $response = $this->get('/up');

    $response->assertStatus(500)
        ->assertSee('Application down');
});

it('keeps /up green when a check has no history yet', function (): void {
    // No records seeded — the dot should be idle, the page should still 200.
    $response = $this->get('/up');

    $response->assertOk()
        ->assertSee('Stub Check')
        ->assertSee('Awaiting the first scheduled probe.');
});

it('renders the per-check detail page', function (): void {
    HealthCheckRecord::create([
        'check_key' => 'stub',
        'healthy' => true,
        'message' => 'Reachable',
        'response_time_ms' => 42,
        'checked_at' => now(),
    ]);

    $response = $this->get('/up/stub');

    $response->assertOk()
        ->assertSee('Stub Check')
        ->assertSee('Heartbeat')
        ->assertSee('Reachable');
});

it('404s the detail page for an unknown check', function (): void {
    $this->get('/up/does-not-exist')->assertNotFound();
});

it('runs health:check on "check now" and redirects back to the detail page', function (): void {
    $response = $this->post('/up/stub/check');

    $response->assertRedirect(route('uptime.detail', 'stub'));

    // The synchronous health:check run persisted via our store.
    expect(HealthCheckRecord::query()->forCheck('stub')->count())->toBe(1);

    // And the redirect flashes the result so the detail page can render it.
    $response->assertSessionHas('checkedResult.healthy', true);
});
