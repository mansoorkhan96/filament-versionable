<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\TranslatablePostResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Mansoor\FilamentVersionable\Page\RevisionsAction;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\TranslatablePostResource;

class EditTranslatablePost extends EditRecord
{
    protected static string $resource = TranslatablePostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RevisionsAction::make(),
            DeleteAction::make(),
        ];
    }
}
