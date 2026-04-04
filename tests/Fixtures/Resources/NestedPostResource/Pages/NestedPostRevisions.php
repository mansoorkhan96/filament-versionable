<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource\Pages;

use Mansoor\FilamentVersionable\RevisionsPage;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource;
use Mansoor\FilamentVersionable\Tests\Fixtures\TestableNestedPage;

class NestedPostRevisions extends RevisionsPage
{
    use TestableNestedPage;

    protected static string $resource = NestedPostResource::class;
}
