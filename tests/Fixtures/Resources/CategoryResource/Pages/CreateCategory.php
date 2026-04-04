<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\CategoryResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\CategoryResource;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
