<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource;

class CreateNestedPost extends CreateRecord
{
    protected static string $resource = NestedPostResource::class;
}
