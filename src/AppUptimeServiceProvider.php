<?php

namespace AlwaysCurious\AppUptime;

use AlwaysCurious\AppUptime\Commands\AppUptimeCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AppUptimeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('app-uptime')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_app_uptime_table')
            ->hasCommand(AppUptimeCommand::class);
    }
}
