<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PostResource;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
