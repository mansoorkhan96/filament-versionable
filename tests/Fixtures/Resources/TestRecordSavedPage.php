<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources;

use Filament\Resources\Pages\Page;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\VersionablePost;

/**
 * Concrete page used to dispatch Filament's RecordSaved event in tests,
 * mirroring what the resource pages do after saving a record.
 */
class TestRecordSavedPage extends Page
{
    protected static string $resource = VersionablePostResource::class;

    public ?VersionablePost $lastSaved = null;

    protected string $view = 'filament-panels::resources.pages.list-records';
}
