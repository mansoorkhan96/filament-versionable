<?php

namespace Mansoor\FilamentVersionable\Page;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class RevisionsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'revisions';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-versionable::actions.revisions'));

        $this->hidden(fn (Model $record) => $record->versions()->count() <= 1);

        $this->button();

        $this->icon('heroicon-m-clock');

        $this->badge(fn (Model $record) => $record->versions()->count() - 1);

        $this->url(function (Model $record, Component $livewire) {
            /** @var Filament\Resources\Resource */
            $resource = app()->make($livewire::getResource());

            return $resource::getUrl('revisions', ['record' => $record]);
        });
    }
}
