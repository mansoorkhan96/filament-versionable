<?php

namespace Mansoor\FilamentVersionable;

use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Overtrue\LaravelVersionable\Version;

class RevisionsPage extends Page
{
    use InteractsWithRecord;
    use WithPagination;

    public Version|Model|null $version;

    protected static string $view = 'filament-versionable::revisions-page';

    public static function getNavigationIcon(): ?string
    {
        return static::$navigationIcon ?? 'heroicon-o-clock';
    }

    public function getBreadcrumb(): string
    {
        return static::$breadcrumb ?? __('filament-versionable::page.breadcrumb');
    }

    public function getContentTabLabel(): ?string
    {
        return __('filament-versionable::page.content_tab_label');
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_if($this->record->versions()->count() <= 1, 404);

        $this->version = $this->record->latestVersion;

        $this->authorizeAccess();
    }

    #[Computed]
    public function diff(): array
    {
        return $this->version
            ->diff()
            ->toSideBySideHtml(
                differOptions: ['fullContextIfIdentical' => true],
                renderOptions: ['lineNumbers' => false, 'showHeader' => false, 'detailLevel' => 'word', 'spacesToNbsp' => false]
            );
    }

    #[Computed]
    public function revisionsList()
    {
        return $this->record
            ->versions()
            ->whereNot('id', $this->record->firstVersion->id)
            ->with('user')
            ->latest()
            ->paginate($this->getRevisionsListPerPage());
    }

    public function showVersion($versionId)
    {
        $this->version = $this->record->getVersion($versionId);
    }

    public function previousVersionAction(): Action
    {
        return Action::make('previousVersion')
            ->label(__('filament-versionable::actions.previous_version'))
            ->disabled(fn () => $this->version->previousVersion()->is($this->record->firstVersion))
            ->action(fn () => $this->previousVersion());
    }

    public function nextVersionAction(): Action
    {
        return Action::make('nextVersion')
            ->label(__('filament-versionable::actions.next_version'))
            ->disabled(fn () => $this->version->is($this->record->lastVersion))
            ->action(fn () => $this->nextVersion());
    }

    public function restoreVersionAction(): Action
    {
        return Action::make('restoreVersion')
            ->label(__('filament-versionable::actions.restore.label'))
            ->requiresConfirmation()
            ->modalDescription(__('filament-versionable::actions.restore.modal_description'))
            ->modalSubmitActionLabel(__('filament-versionable::actions.restore.modal_submit_action_label'))
            ->disabled(fn () => $this->version->is($this->record->lastVersion))
            ->action(fn () => $this->restoreVersion());
    }

    public function previousVersion()
    {
        $this->version = $this->version->previousVersion();
    }

    public function nextVersion()
    {
        $this->version = $this->version->nextVersion();
    }

    public function restoreVersion()
    {
        $this->version->revert();

        return redirect(static::$resource::getUrl('edit', ['record' => $this->getRecord()]));
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }

    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        $url = static::$resource::getUrl('edit', ['record' => $this->getRecord()]);

        return new HtmlString(__('filament-versionable::page.title', [
            'title' => "<a href=\"{$url}\" class=\"text-primary-500\">
                {$this->getRecordTitle()}
            </a>",
        ]));
    }

    public function getRevisionsListPerPage(): int
    {
        return 10;
    }
}
