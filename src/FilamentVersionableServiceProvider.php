<?php

namespace Mansoor\FilamentVersionable;

use Mansoor\FilamentVersionable\Support\RelationSnapshotManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentVersionableServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-versionable';

    public static string $viewNamespace = 'filament-versionable';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasAssets()
            ->hasViews()
            ->hasMigrations('add_relations_to_versions_table');
    }

    public function packageBooted(): void
    {
        RelationSnapshotManager::registerEventHooks();
    }
}
