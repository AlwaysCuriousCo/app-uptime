<?php

namespace AlwaysCurious\AppUptime\Tests;

use AlwaysCurious\AppUptime\AppUptimeServiceProvider;
use AlwaysCurious\AppUptime\HealthCheckRecordStore;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Health\HealthServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            // Spatie's provider must boot before ours because our routes are
            // registered in packageBooted() and reference its Health service.
            HealthServiceProvider::class,
            AppUptimeServiceProvider::class,
        ];
    }

    /**
     * Configure the test environment: an in-memory SQLite database, and
     * spatie/laravel-health wired to persist results through our store.
     */
    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Route the test app's health results into our package store so
        // running `health:check` populates the `health_check_records` table
        // the UI queries.
        $app['config']->set('health.result_stores', [HealthCheckRecordStore::class]);
    }

    /**
     * Run the package's migration against the in-memory database. The file is
     * a `.stub` so Testbench's `loadMigrationsFrom` won't pick it up — include
     * it directly and call `up()`.
     */
    protected function defineDatabaseMigrations(): void
    {
        $migration = require __DIR__.'/../database/migrations/create_health_check_records_table.php.stub';

        $migration->up();
    }
}
