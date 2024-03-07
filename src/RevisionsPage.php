<?php

namespace Mansoor\FilamentVersionable;

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

    public Version | Model | null $version;

    protected static string $view = 'filament-versionable::revisions-page';

    public static function getNavigationIcon(): ?string
    {
        return static::$navigationIcon ?? 'heroicon-o-clock';
    }

    public function getBreadcrumb(): string
    {
        return static::$breadcrumb ?? 'Revisions';
    }

    public function getContentTabLabel(): ?string
    {
        return 'Revisions';
    }

    public function mount(int | string $record): void
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
            ->diff($this->version->previousVersion())
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

    public function showPreviousVersion()
    {
        $this->version = $this->version->previousVersion();
    }

    public function showNextVersion()
    {
        $this->version = $this->version->nextVersion();
    }

    public function restoreSelectedVersion()
    {
        $this->version->revert();

        return redirect(static::$resource::getUrl('edit', ['record' => $this->getRecord()]));
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }

    public function getTitle(): string | Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        $url = static::$resource::getUrl('edit', ['record' => $this->getRecord()]);

        return new HtmlString("
            Compare Revisions of
            <a href=\"{$url}\" class=\"text-primary-500\">
                {$this->getRecordTitle()}
            </a>
        ");
    }

    public function getRevisionsListPerPage(): int
    {
        return 10;
    }
}
