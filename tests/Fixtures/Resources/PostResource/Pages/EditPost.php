<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PostResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Mansoor\FilamentVersionable\Page\RevisionsAction;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PostResource;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RevisionsAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
