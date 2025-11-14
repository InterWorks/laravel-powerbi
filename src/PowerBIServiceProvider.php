<?php

namespace InterWorks\PowerBI;

use InterWorks\PowerBI\Commands\PowerBICommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PowerBIServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-powerbi')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_powerbi_table')
            ->hasCommand(PowerBICommand::class);
    }
}
