<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\TranslatablePostResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\TranslatablePostResource;

class ListTranslatablePosts extends ListRecords
{
    protected static string $resource = TranslatablePostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
