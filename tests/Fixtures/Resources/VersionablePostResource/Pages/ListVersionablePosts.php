<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\VersionablePostResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\VersionablePostResource;

class ListVersionablePosts extends ListRecords
{
    protected static string $resource = VersionablePostResource::class;
}
