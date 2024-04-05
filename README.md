# Filament Versionable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mansoor/filament-versionable.svg?style=flat-square)](https://packagist.org/packages/mansoor/filament-versionable)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mansoor/filament-versionable/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mansoor/filament-versionable/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mansoor/filament-versionable/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mansoor/filament-versionable/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mansoor/filament-versionable.svg?style=flat-square)](https://packagist.org/packages/mansoor/filament-versionable)

A Filament plugin and a wrapper around [Laravel Versionable](https://github.com/overtrue/laravel-versionable) to create versions Laravel Models. When ever you save a model, it would store the specified `$versionable` fields to the Database and then you can revert to any target model state any time.

![](./screenshot.png)

## Installation

You can install the package via composer:

```bash
composer require mansoor/filament-versionable
```

Then, publish the config file and migrations:

```bash
php artisan vendor:publish --provider="Overtrue\LaravelVersionable\ServiceProvider"
```

Finally, run the migration:

```bash
php artisan migrate
```

## Usage

Add `Overtrue\LaravelVersionable\Versionable` trait to your model and set `$versionable` attributes.

**NOTE: Make sure to add protected $versionStrategy = VersionStrategy::SNAPSHOT; This would save all the $versionable attributes when any of them changed. There are different bug reports on using VersionStrategy::DIFF**

```php
use Overtrue\LaravelVersionable\VersionStrategy;

class Post extends Model
{
    use Overtrue\LaravelVersionable\Versionable;

    protected $versionable = ['title', 'content'];

    protected $versionStrategy = VersionStrategy::SNAPSHOT;
}
```

Create a Revisons Resource Page to Show Revisions, it should extend the `Mansoor\FilamentVersionable\RevisionsPage`. The page should look like:

```php
namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Mansoor\FilamentVersionable\RevisionsPage;

class ArticleRevisions extends RevisionsPage
{
    protected static string $resource = ArticleResource::class;
}
```

Next, Add the Revisions page to your Resource

```php
use App\Filament\Resources\ArticleResource\Pages;

public static function getPages(): array
{
    return [
        ...
        'revisions' => Pages\ArticleRevisions::route('/{record}/revisions'),
    ];
}
```

Add `RevisionsAction` to your page, this action would appear when there are any versions for the model your are viewing/editing.

```php
use Mansoor\FilamentVersionable\Page\RevisionsAction;

protected function getActions(): array
{
    return [
        RevisionsAction::make(),
    ];
}
```

You can also show the `RevisionsAction` on your table rows.

```php
use Mansoor\FilamentVersionable\Table\RevisionsAction;

$table->filters([
    RevisionsAction::make(),
]);
```

You are all set! Your app should store the model states and you can manage them using Filament.

## Customisation

If you want to change the UI for Revisions page, you may publish the publish the views to do so.

```bash
php artisan vendor:publish --tag="filament-versionable-views"
```

If you want more control over how the versions are stored, you may read the [Laravel Versionable Docs](https://github.com/overtrue/laravel-versionable).

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mansoor Ahmed](https://github.com/mansoorkhan96)
- [安正超](https://github.com/overtrue) for [Laravel Versionable](https://github.com/overtrue/laravel-versionable)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
