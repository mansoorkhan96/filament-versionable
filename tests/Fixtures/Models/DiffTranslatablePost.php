<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Overtrue\LaravelVersionable\VersionStrategy;

class DiffTranslatablePost extends TranslatablePost
{
    protected $table = 'translatable_posts';

    protected $versionStrategy = VersionStrategy::DIFF;
}
