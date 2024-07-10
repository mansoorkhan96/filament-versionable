<?php

namespace Mansoor\FilamentVersionable;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
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
            ->hasViews();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make(static::$name, __DIR__.'/../resources/dist/filament-versionable.css')->loadedOnRequest(),
        ], static::$name);
    }
}
