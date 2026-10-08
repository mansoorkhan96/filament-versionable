<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\VersionablePostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\VersionablePostResource;

class CreateVersionablePost extends CreateRecord
{
    protected static string $resource = VersionablePostResource::class;
}
