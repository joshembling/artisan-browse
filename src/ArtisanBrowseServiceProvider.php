<?php

namespace JoshEmbling\ArtisanBrowse;

use JoshEmbling\ArtisanBrowse\Commands\ArtisanBrowseCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ArtisanBrowseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('artisan-browse')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_artisan_browse_table')
            ->hasCommand(ArtisanBrowseCommand::class);
    }
}
