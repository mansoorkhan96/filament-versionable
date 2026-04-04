<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource;
use Mansoor\FilamentVersionable\Tests\Fixtures\TestableNestedPage;

class ListNestedPosts extends ListRecords
{
    use TestableNestedPage;

    protected static string $resource = NestedPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
