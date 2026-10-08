<?php

namespace Mansoor\FilamentVersionable;

use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Mansoor\FilamentVersionable\Support\RelationChangeSet;
use Mansoor\FilamentVersionable\Support\RelationDiff;
use Mansoor\FilamentVersionable\Support\RelationSnapshotManager;
use Overtrue\LaravelVersionable\Version;

class RevisionsPage extends Page
{
    use InteractsWithRecord;
    use WithPagination;

    public Version|Model|null $version;

    protected string $view = 'filament-versionable::revisions-page';

    public function shouldStripTags(): bool
    {
        return false;
    }

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

        $this->authorizeAccess();

        $this->version = $this->record->latestVersion;
    }

    public function hasRevisions(): bool
    {
        return $this->record->versions()->count() > 1;
    }

    #[Computed]
    public function diff(): array
    {
        return $this->version
            ->diff()
            ->toSideBySideHtml(
                differOptions: ['fullContextIfIdentical' => true],
                renderOptions: ['lineNumbers' => false, 'showHeader' => false, 'detailLevel' => 'word', 'spacesToNbsp' => false],
                stripTags: $this->shouldStripTags()
            );
    }

    /**
     * Per-relation change sets between the selected version and its
     * predecessor, computed from the stored relationship snapshots.
     *
     * @return list<RelationChangeSet>
     */
    #[Computed]
    public function relationDiffs(): array
    {
        $version = $this->version;

        if (! $version instanceof Version) {
            return [];
        }

        return (new RelationDiff(
            newVersion: $version,
            oldVersion: $version->previousVersion(),
            differOptions: ['fullContextIfIdentical' => true],
            renderOptions: ['lineNumbers' => false, 'showHeader' => false, 'detailLevel' => 'word', 'spacesToNbsp' => false],
            stripTags: $this->shouldStripTags(),
        ))->changes();
    }

    #[Computed]
    public function revisionsList(): LengthAwarePaginator
    {
        $query = $this->record->versions();

        if ($firstVersion = $this->record->firstVersion) {
            $query->whereNot('id', $firstVersion->id);
        }

        return $query
            ->with('user')
            ->latest()
            ->paginate($this->getRevisionsListPerPage());
    }

    public function showVersion(int|string $versionId): void
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
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(__('filament-versionable::actions.restore.modal_description'))
            ->modalSubmitActionLabel(__('filament-versionable::actions.restore.modal_submit_action_label'))
            ->action(fn () => $this->restoreVersion());
    }

    public function previousVersion(): void
    {
        $this->version = $this->version->previousVersion();
    }

    public function nextVersion(): void
    {
        $this->version = $this->version->nextVersion();
    }

    public function restoreVersion(): void
    {
        $target = $this->version->previousVersion();

        if ($target === null) {
            return;
        }

        DB::transaction(function () use ($target): void {
            $target->revert();

            $record = $target->versionable;

            if ($record instanceof Model && RelationSnapshotManager::usesVersionableRelations($record)) {
                $snapshot = RelationSnapshotManager::forVersion($target);

                if ($snapshot !== []) {
                    RelationSnapshotManager::restore($record, $snapshot);
                }

                // The revert itself created a version — refresh its
                // relationship snapshot to the post-restore state.
                $created = method_exists($record, 'latestVersion')
                    ? $record->latestVersion()->first()
                    : null;

                if ($created !== null && $created->isNot($target)) {
                    RelationSnapshotManager::storeSnapshot(
                        $created,
                        RelationSnapshotManager::captureSnapshot($record)
                    );
                }
            }
        });

        $parameters = ['record' => $this->getRecord()];

        $parentRegistration = static::getResource()::getParentResourceRegistration();

        if ($parentRegistration) {
            $parentRecord = $this->getParentRecord();

            if ($parentRecord) {
                $parameters[$parentRegistration->getParentRouteParameterName()] = $parentRecord;
            }
        }

        $this->redirect(static::$resource::getUrl('edit', $parameters));
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

        return $this->getRecordTitle();
    }

    public function getRevisionsListPerPage(): int
    {
        return 10;
    }
}
