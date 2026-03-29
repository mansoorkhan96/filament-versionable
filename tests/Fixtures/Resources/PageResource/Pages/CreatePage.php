<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PageResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;
}
