<?php

use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Page;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\User;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PageResource\Pages\PageRevisions;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = createUser();
    $this->actingAs($this->user);
});

function createPageWithVersions(User $user, int $versionCount = 3): Page
{
    $page = Page::create([
        'title' => 'Version 1 Title',
        'slug' => 'version-1-slug',
        'content' => 'Version 1 Content',
    ]);

    for ($i = 2; $i <= $versionCount; $i++) {
        $page->update([
            'title' => "Version {$i} Title",
            'slug' => "version-{$i}-slug",
            'content' => "Version {$i} Content",
        ]);
    }

    return $page->refresh();
}

it('can mount the revisions page for a page without user_id', function () {
    $page = createPageWithVersions($this->user);

    livewire(PageRevisions::class, ['record' => $page->getKey()])
        ->assertOk();
});

it('computes diff correctly for page model', function () {
    $page = Page::create([
        'title' => 'Original Title',
        'slug' => 'original-slug',
        'content' => 'Original Content',
    ]);

    $page->update([
        'title' => 'Updated Title',
        'slug' => 'updated-slug',
        'content' => 'Updated Content',
    ]);

    $page->refresh();

    $component = livewire(PageRevisions::class, ['record' => $page->getKey()]);

    $diff = $component->instance()->diff;
    expect($diff)->toBeArray();
    expect($diff)->toHaveKey('title');
    expect($diff)->toHaveKey('slug');
    expect($diff)->toHaveKey('content');
});

it('can restore a version for page model', function () {
    $page = Page::create([
        'title' => 'Original Title',
        'slug' => 'original-slug',
        'content' => 'Original Content',
    ]);

    $page->update([
        'title' => 'Updated Title',
        'slug' => 'updated-slug',
        'content' => 'Updated Content',
    ]);

    $page->refresh();

    livewire(PageRevisions::class, ['record' => $page->getKey()])
        ->callAction('restoreVersion')
        ->assertRedirect();

    $page->refresh();
    expect($page->title)->toBe('Original Title');
    expect($page->slug)->toBe('original-slug');
    expect($page->content)->toBe('Original Content');
});

it('shows authenticated user name for page revisions', function () {
    $page = createPageWithVersions($this->user);

    livewire(PageRevisions::class, ['record' => $page->getKey()])
        ->assertSee($this->user->name);
});

it('shows anonymous user when version has no user', function () {
    $page = Page::create([
        'title' => 'Title 1',
        'slug' => 'slug-1',
        'content' => 'Content 1',
    ]);

    // Manually nullify user_id on the version to simulate no authenticated user
    $page->versions()->update(['user_id' => null]);

    $page->update([
        'title' => 'Title 2',
        'slug' => 'slug-2',
        'content' => 'Content 2',
    ]);

    $page->refresh();

    // The first version has null user_id, second has auth user
    // The revisions list shows "Anonymous User" for versions without a user
    livewire(PageRevisions::class, ['record' => $page->getKey()])
        ->assertSee(__('filament-versionable::page.anonymous_user'));
});

it('can navigate between versions for page model', function () {
    $page = createPageWithVersions($this->user, 4);

    $latestVersion = $page->latestVersion;
    $previousVersion = $latestVersion->previousVersion();

    livewire(PageRevisions::class, ['record' => $page->getKey()])
        ->assertSet('version.id', $latestVersion->id)
        ->callAction('previousVersion')
        ->assertSet('version.id', $previousVersion->id)
        ->callAction('nextVersion')
        ->assertSet('version.id', $latestVersion->id);
});

it('displays revisions list for page model', function () {
    $page = createPageWithVersions($this->user, 4);

    $component = livewire(PageRevisions::class, ['record' => $page->getKey()]);

    $revisionsList = $component->instance()->revisionsList;
    expect($revisionsList)->toHaveCount(3);
});

it('stores auth user id in page versions', function () {
    $page = Page::create([
        'title' => 'Test Page',
        'slug' => 'test-page',
        'content' => 'Some content',
    ]);

    expect($page->versions)->toHaveCount(1);
    expect($page->versions->first()->user_id)->toBe($this->user->id);
});
