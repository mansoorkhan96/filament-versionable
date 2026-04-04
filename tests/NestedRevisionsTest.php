<?php

use Filament\Actions\Testing\TestAction;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Category;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Post;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\User;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource\Pages\EditNestedPost;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource\Pages\ListNestedPosts;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource\Pages\NestedPostRevisions;

beforeEach(function () {
    $this->user = createUser();
    $this->actingAs($this->user);
    $this->category = Category::create(['name' => 'Test Category']);
});

afterEach(function () {
    // Clean up static test state
    NestedPostRevisions::$testParentRecord = null;
    EditNestedPost::$testParentRecord = null;
    ListNestedPosts::$testParentRecord = null;
});

function createNestedPostWithVersions(User $user, Category $category, int $versionCount = 3): Post
{
    $post = Post::create([
        'title' => 'Version 1 Title',
        'content' => 'Version 1 Content',
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    for ($i = 2; $i <= $versionCount; $i++) {
        $post->update([
            'title' => "Version {$i} Title",
            'content' => "Version {$i} Content",
        ]);
    }

    return $post->refresh();
}

function nestedRevisionsUrl(Post $post, Category $category): string
{
    return NestedPostResource::getUrl('revisions', [
        'record' => $post,
        'category' => $category,
    ]);
}

function nestedEditUrl(Post $post, Category $category): string
{
    return NestedPostResource::getUrl('edit', [
        'record' => $post,
        'category' => $category,
    ]);
}

function nestedListUrl(Category $category): string
{
    return NestedPostResource::getUrl('index', [
        'category' => $category,
    ]);
}

// ─── Nested RevisionsPage ──────────────────────────────────────────────

describe('Nested RevisionsPage', function () {
    it('can mount the revisions page via HTTP', function () {
        $post = createNestedPostWithVersions($this->user, $this->category);

        $this->get(nestedRevisionsUrl($post, $this->category))
            ->assertOk();
    });

    it('can mount the revisions page via livewire', function () {
        $post = createNestedPostWithVersions($this->user, $this->category);

        NestedPostRevisions::$testParentRecord = $this->category;

        livewire(NestedPostRevisions::class, ['record' => $post->getKey()])
            ->assertOk();
    });

    it('shows empty state when record has only one version', function () {
        $post = Post::create([
            'title' => 'Only Version',
            'content' => 'Only Content',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        NestedPostRevisions::$testParentRecord = $this->category;

        livewire(NestedPostRevisions::class, ['record' => $post->getKey()])
            ->assertOk()
            ->assertSee('No revisions available');
    });

    it('shows empty state when record has no versions', function () {
        Post::withoutVersion(function () {
            $this->post = Post::create([
                'title' => 'No Versions',
                'content' => 'No Content',
                'user_id' => $this->user->id,
                'category_id' => $this->category->id,
            ]);
        });

        NestedPostRevisions::$testParentRecord = $this->category;

        livewire(NestedPostRevisions::class, ['record' => $this->post->getKey()])
            ->assertOk()
            ->assertSee('No revisions available');
    });

    it('shows the latest version by default', function () {
        $post = createNestedPostWithVersions($this->user, $this->category, 3);

        NestedPostRevisions::$testParentRecord = $this->category;

        livewire(NestedPostRevisions::class, ['record' => $post->getKey()])
            ->assertOk()
            ->assertSet('version.id', $post->latestVersion->id);
    });

    it('computes diff between current and previous version', function () {
        $post = Post::create([
            'title' => 'Original Title',
            'content' => 'Original Content',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update([
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ]);

        $post->refresh();

        NestedPostRevisions::$testParentRecord = $this->category;

        $component = livewire(NestedPostRevisions::class, ['record' => $post->getKey()]);

        $diff = $component->instance()->diff;
        expect($diff)->toBeArray();
        expect($diff)->toHaveKey('title');
        expect($diff)->toHaveKey('content');
    });

    it('can navigate to previous version', function () {
        $post = createNestedPostWithVersions($this->user, $this->category, 4);

        $latestVersion = $post->latestVersion;
        $previousVersion = $latestVersion->previousVersion();

        NestedPostRevisions::$testParentRecord = $this->category;

        livewire(NestedPostRevisions::class, ['record' => $post->getKey()])
            ->assertSet('version.id', $latestVersion->id)
            ->callAction('previousVersion')
            ->assertSet('version.id', $previousVersion->id);
    });

    it('can navigate to next version', function () {
        $post = createNestedPostWithVersions($this->user, $this->category, 4);

        $latestVersion = $post->latestVersion;
        $previousVersion = $latestVersion->previousVersion();

        NestedPostRevisions::$testParentRecord = $this->category;

        livewire(NestedPostRevisions::class, ['record' => $post->getKey()])
            ->callAction('previousVersion')
            ->assertSet('version.id', $previousVersion->id)
            ->callAction('nextVersion')
            ->assertSet('version.id', $latestVersion->id);
    });

    it('can show a specific version', function () {
        $post = createNestedPostWithVersions($this->user, $this->category, 4);

        $versions = $post->versions()->orderBy('id', 'asc')->get();
        $secondVersion = $versions[1];

        NestedPostRevisions::$testParentRecord = $this->category;

        livewire(NestedPostRevisions::class, ['record' => $post->getKey()])
            ->call('showVersion', $secondVersion->getKey())
            ->assertSet('version.id', $secondVersion->id);
    });

    it('can restore a version and redirects to nested edit URL', function () {
        $post = Post::create([
            'title' => 'Original Title',
            'content' => 'Original Content',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update([
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ]);

        $post->refresh();

        $expectedEditUrl = nestedEditUrl($post, $this->category);
        expect($expectedEditUrl)->toContain("/categories/{$this->category->getKey()}/");

        NestedPostRevisions::$testParentRecord = $this->category;

        livewire(NestedPostRevisions::class, ['record' => $post->getKey()])
            ->callAction('restoreVersion')
            ->assertRedirect($expectedEditUrl);

        $post->refresh();
        expect($post->title)->toBe('Original Title');
        expect($post->content)->toBe('Original Content');
    });

    it('restore action requires confirmation', function () {
        $post = createNestedPostWithVersions($this->user, $this->category);

        NestedPostRevisions::$testParentRecord = $this->category;

        livewire(NestedPostRevisions::class, ['record' => $post->getKey()])
            ->mountAction('restoreVersion')
            ->assertActionMounted('restoreVersion');
    });

    it('shows the revision author name', function () {
        $post = createNestedPostWithVersions($this->user, $this->category);

        $this->get(nestedRevisionsUrl($post, $this->category))
            ->assertOk()
            ->assertSee($this->user->name);
    });

    it('displays revisions list excluding first version', function () {
        $post = createNestedPostWithVersions($this->user, $this->category, 4);

        NestedPostRevisions::$testParentRecord = $this->category;

        $component = livewire(NestedPostRevisions::class, ['record' => $post->getKey()]);

        $revisionsList = $component->instance()->revisionsList;
        expect($revisionsList)->toHaveCount(3);
    });

    it('shows field names in the diff view', function () {
        $post = Post::create([
            'title' => 'Original',
            'content' => 'Original Content',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update([
            'title' => 'Changed',
            'content' => 'Changed Content',
        ]);

        $post->refresh();

        $this->get(nestedRevisionsUrl($post, $this->category))
            ->assertOk()
            ->assertSee('title')
            ->assertSee('content');
    });

    it('can navigate back and forth between versions', function () {
        $post = createNestedPostWithVersions($this->user, $this->category, 5);

        $versions = $post->versions()->orderBy('id', 'desc')->get();
        $latest = $versions[0];
        $prev1 = $versions[1];
        $prev2 = $versions[2];

        NestedPostRevisions::$testParentRecord = $this->category;

        livewire(NestedPostRevisions::class, ['record' => $post->getKey()])
            ->assertSet('version.id', $latest->id)
            ->callAction('previousVersion')
            ->assertSet('version.id', $prev1->id)
            ->callAction('previousVersion')
            ->assertSet('version.id', $prev2->id)
            ->callAction('nextVersion')
            ->assertSet('version.id', $prev1->id)
            ->callAction('nextVersion')
            ->assertSet('version.id', $latest->id);
    });

    it('shows breadcrumb and content tab label', function () {
        $post = createNestedPostWithVersions($this->user, $this->category);

        NestedPostRevisions::$testParentRecord = $this->category;

        $component = livewire(NestedPostRevisions::class, ['record' => $post->getKey()]);

        expect($component->instance()->getBreadcrumb())
            ->toBe(__('filament-versionable::page.breadcrumb'));
        expect($component->instance()->getContentTabLabel())
            ->toBe(__('filament-versionable::page.content_tab_label'));
    });
});

// ─── Nested Page RevisionsAction ───────────────────────────────────────

describe('Nested Page RevisionsAction', function () {
    it('is visible on nested edit page when record has multiple versions', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        $this->get(nestedEditUrl($post, $this->category))
            ->assertOk()
            ->assertSee(__('filament-versionable::actions.revisions'));
    });

    it('is hidden on nested edit page when record has only one version', function () {
        $post = Post::create([
            'title' => 'Only Version',
            'content' => 'Only Content',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $this->get(nestedEditUrl($post, $this->category))
            ->assertOk()
            ->assertDontSee(__('filament-versionable::actions.revisions'));
    });

    it('has the correct URL pointing to nested revisions page', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        $expectedRevisionsUrl = nestedRevisionsUrl($post, $this->category);
        expect($expectedRevisionsUrl)->toContain("/categories/{$this->category->getKey()}/");

        $this->get(nestedEditUrl($post, $this->category))
            ->assertOk()
            ->assertSee($expectedRevisionsUrl);
    });

    it('has the correct icon', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        EditNestedPost::$testParentRecord = $this->category;

        livewire(EditNestedPost::class, ['record' => $post->getKey()])
            ->assertActionHasIcon('revisions', 'heroicon-m-clock');
    });

    it('has the correct label', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        EditNestedPost::$testParentRecord = $this->category;

        livewire(EditNestedPost::class, ['record' => $post->getKey()])
            ->assertActionHasLabel('revisions', __('filament-versionable::actions.revisions'));
    });
});

// ─── Nested Table RevisionsAction ──────────────────────────────────────

describe('Nested Table RevisionsAction', function () {
    it('renders the nested list page', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        $this->get(nestedListUrl($this->category))
            ->assertOk();
    });

    it('has the correct URL pointing to nested revisions page from table', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        $expectedRevisionsUrl = nestedRevisionsUrl($post, $this->category);
        expect($expectedRevisionsUrl)->toContain("/categories/{$this->category->getKey()}/");

        $this->get(nestedListUrl($this->category))
            ->assertOk()
            ->assertSee($expectedRevisionsUrl);
    });

    it('is visible in nested table when record has multiple versions', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        ListNestedPosts::$testParentRecord = $this->category;

        livewire(ListNestedPosts::class)
            ->assertActionVisible(TestAction::make('revisions')->table($post));
    });

    it('is hidden in nested table when record has only one version', function () {
        $post = Post::create([
            'title' => 'Only Version',
            'content' => 'Only Content',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        ListNestedPosts::$testParentRecord = $this->category;

        livewire(ListNestedPosts::class)
            ->assertActionHidden(TestAction::make('revisions')->table($post));
    });

    it('has the correct icon in nested table', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        ListNestedPosts::$testParentRecord = $this->category;

        livewire(ListNestedPosts::class)
            ->assertActionHasIcon(TestAction::make('revisions')->table($post), 'heroicon-m-clock');
    });

    it('has the correct label in nested table', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        ListNestedPosts::$testParentRecord = $this->category;

        livewire(ListNestedPosts::class)
            ->assertActionHasLabel(
                TestAction::make('revisions')->table($post),
                __('filament-versionable::actions.revisions'),
            );
    });
});
