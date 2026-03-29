<?php

namespace Mansoor\FilamentVersionable\Page;

use Filament\Actions\Action;
use Filament\Resources\Resource;
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
            /** @var Resource $resource */
            $resource = app()->make($livewire::getResource());

            $parameters = ['record' => $record];

            $parentRegistration = $resource::getParentResourceRegistration();

            if ($parentRegistration && method_exists($livewire, 'getParentRecord')) {
                $parentRecord = $livewire->getParentRecord();

                if ($parentRecord) {
                    $parameters[$parentRegistration->getParentRouteParameterName()] = $parentRecord;
                }
            }

            return $resource::getUrl('revisions', $parameters);
        });
    }
}
