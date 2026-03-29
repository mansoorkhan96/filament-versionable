<?php

namespace Mansoor\FilamentVersionable\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\Livewire\Partials\DataStoreOverride;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Mansoor\FilamentVersionable\FilamentVersionableServiceProvider;
use Mansoor\FilamentVersionable\Tests\Fixtures\AdminPanelProvider;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\User;
use Orchestra\Testbench\TestCase as Orchestra;
use Overtrue\LaravelVersionable\ServiceProvider as VersionableServiceProvider;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        // Share errors with views (normally done by ShareErrorsFromSession middleware)
        View::share('errors', new ViewErrorBag);
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            VersionableServiceProvider::class,
            FilamentVersionableServiceProvider::class,
            AdminPanelProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('auth.providers.users.model', User::class);
        config()->set('versionable.user_model', User::class);

        // Workaround: Filament v4 beta registers DataStoreOverride with bind() instead
        // of singleton(), causing a new DataStore instance (with a fresh, empty WeakMap)
        // on every app(DataStore::class) call. This breaks Livewire's component state
        // management (e.g., getErrorBag() returns null). Re-register as singleton.
        $app->singleton(DataStore::class, DataStoreOverride::class);
    }

    protected function setUpDatabase(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->text('content')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Load versions table migrations from overtrue/laravel-versionable
        $this->loadMigrationsFrom(__DIR__.'/../vendor/overtrue/laravel-versionable/migrations');
    }
}
