<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Mansoor\FilamentVersionable\Versionable;
use Overtrue\LaravelVersionable\VersionStrategy;

class Page extends Model
{
    use Versionable;

    protected $fillable = ['title', 'slug', 'content'];

    protected $versionable = ['title', 'slug', 'content'];

    protected $versionStrategy = VersionStrategy::SNAPSHOT;
}
