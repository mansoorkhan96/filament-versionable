<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\CategoryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\CategoryResource;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;
}
