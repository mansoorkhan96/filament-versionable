<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Mansoor\FilamentVersionable\Page\RevisionsAction;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource;
use Mansoor\FilamentVersionable\Tests\Fixtures\TestableNestedPage;

class EditNestedPost extends EditRecord
{
    use TestableNestedPage;

    protected static string $resource = NestedPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RevisionsAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
