<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\VersionablePostResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\VersionablePostResource;

class EditVersionablePost extends EditRecord
{
    protected static string $resource = VersionablePostResource::class;
}
