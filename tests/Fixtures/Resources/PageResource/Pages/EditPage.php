<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PageResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Mansoor\FilamentVersionable\Page\RevisionsAction;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PageResource;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RevisionsAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
