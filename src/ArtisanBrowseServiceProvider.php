<?php

namespace JoshEmbling\ArtisanBrowse;

use JoshEmbling\ArtisanBrowse\Commands\ArtisanBrowseCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
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
            ->hasConfigFile('artisan-browse')
            ->hasCommand(ArtisanBrowseCommand::class)
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->askToStarRepoOnGitHub('joshembling/artisan-browse');
            });
    }
}
