<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\TranslatablePostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\TranslatablePostResource;

class CreateTranslatablePost extends CreateRecord
{
    protected static string $resource = TranslatablePostResource::class;
}
