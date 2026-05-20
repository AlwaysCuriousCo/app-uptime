<?php

namespace AlwaysCurious\AppUptime;

use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Registers the package: publishes the `uptime` config, loads the
 * `health_check_records` migration, exposes views under the `uptime::`
 * namespace, and (by default) mounts the `/up` routes.
 *
 * The package depends on spatie/laravel-health for check registration and
 * running — wire your checks in your own provider via `Health::checks([...])`
 * and add {@see HealthCheckRecordStore}::class to `config('health.result_stores')`
 * so probe outcomes land in this package's table.
 */
class AppUptimeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('app-uptime')
            ->hasConfigFile('uptime')
            ->hasViews('uptime')
            ->hasMigration('create_health_check_records_table');
    }

    /**
     * Mount the `/up` routes after the config is merged, gated by
     * `config('uptime.routes.enabled')`. Apps that prefer to register their
     * own routes (e.g. behind custom middleware or at a different URL) can
     * set `routes.enabled => false` in `config/uptime.php`.
     */
    public function packageBooted(): void
    {
        if (! config('uptime.routes.enabled', true)) {
            return;
        }

        Route::group([
            'prefix' => config('uptime.routes.prefix', 'up'),
            'middleware' => config('uptime.routes.middleware', []),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }
}
