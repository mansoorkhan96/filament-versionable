<?php

use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Post;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\User;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PostResource\Pages\PostRevisions;

beforeEach(function () {
    $this->user = createUser();
    $this->actingAs($this->user);
});

it('can mount the revisions page', function () {
    $post = createPostWithVersions($this->user);

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->assertOk();
});

it('aborts with 404 when record has only one version', function () {
    $post = Post::create([
        'title' => 'Only Version',
        'content' => 'Only Content',
        'user_id' => $this->user->id,
    ]);

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->assertNotFound();
});

it('aborts with 404 when record has no versions', function () {
    Post::withoutVersion(function () {
        $this->post = Post::create([
            'title' => 'No Versions',
            'content' => 'No Content',
            'user_id' => $this->user->id,
        ]);
    });

    livewire(PostRevisions::class, ['record' => $this->post->getKey()])
        ->assertNotFound();
});

it('shows the latest version by default', function () {
    $post = createPostWithVersions($this->user, 3);

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->assertOk()
        ->assertSet('version.id', $post->latestVersion->id);
});

it('computes diff between current and previous version', function () {
    $post = Post::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
        'user_id' => $this->user->id,
    ]);

    $post->update([
        'title' => 'Updated Title',
        'content' => 'Updated Content',
    ]);

    $post->refresh();

    $component = livewire(PostRevisions::class, ['record' => $post->getKey()]);

    // Verify the diff computed property returns data for each changed field
    $diff = $component->instance()->diff;
    expect($diff)->toBeArray();
    expect($diff)->toHaveKey('title');
    expect($diff)->toHaveKey('content');
});

it('can navigate to previous version', function () {
    $post = createPostWithVersions($this->user, 4);

    $latestVersion = $post->latestVersion;
    $previousVersion = $latestVersion->previousVersion();

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->assertSet('version.id', $latestVersion->id)
        ->callAction('previousVersion')
        ->assertSet('version.id', $previousVersion->id);
});

it('can navigate to next version', function () {
    $post = createPostWithVersions($this->user, 4);

    $latestVersion = $post->latestVersion;
    $previousVersion = $latestVersion->previousVersion();

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->callAction('previousVersion')
        ->assertSet('version.id', $previousVersion->id)
        ->callAction('nextVersion')
        ->assertSet('version.id', $latestVersion->id);
});

it('can show a specific version', function () {
    $post = createPostWithVersions($this->user, 4);

    $versions = $post->versions()->orderBy('id', 'asc')->get();
    $secondVersion = $versions[1]; // Second version (index 1)

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->call('showVersion', $secondVersion->getKey())
        ->assertSet('version.id', $secondVersion->id);
});

it('can restore a version via the restore action', function () {
    $post = Post::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
        'user_id' => $this->user->id,
    ]);

    $post->update([
        'title' => 'Updated Title',
        'content' => 'Updated Content',
    ]);

    $post->refresh();

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->callAction('restoreVersion')
        ->assertRedirect();

    $post->refresh();
    expect($post->title)->toBe('Original Title');
    expect($post->content)->toBe('Original Content');
});

it('restore action requires confirmation', function () {
    $post = createPostWithVersions($this->user);

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->mountAction('restoreVersion')
        ->assertActionMounted('restoreVersion');
        // ->assertMountedActionModalSee(__('filament-versionable::actions.restore.modal_description'));
});

it('shows the revision author name', function () {
    $post = createPostWithVersions($this->user);

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->assertSee($this->user->name);
});

it('displays revisions list excluding first version', function () {
    $post = createPostWithVersions($this->user, 4);

    $component = livewire(PostRevisions::class, ['record' => $post->getKey()]);

    // The revisions list should exclude the first version
    // So for 4 versions, we should see 3 in the list
    $revisionsList = $component->instance()->revisionsList;
    expect($revisionsList)->toHaveCount(3);
});

it('paginates revisions list', function () {
    $post = createPostWithVersions($this->user, 15);

    $component = livewire(PostRevisions::class, ['record' => $post->getKey()]);

    // Default pagination is 10
    $revisionsList = $component->instance()->revisionsList;
    expect($revisionsList->count())->toBe(10);
    expect($revisionsList->total())->toBe(14); // 15 versions minus the first
});

it('shows field names in the diff view', function () {
    $post = Post::create([
        'title' => 'Original',
        'content' => 'Original Content',
        'user_id' => $this->user->id,
    ]);

    $post->update([
        'title' => 'Changed',
        'content' => 'Changed Content',
    ]);

    $post->refresh();

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->assertSee('title')
        ->assertSee('content');
});

it('handles json metadata in versions', function () {
    $post = Post::create([
        'title' => 'Title',
        'content' => 'Content',
        'metadata' => ['key' => 'value1'],
        'user_id' => $this->user->id,
    ]);

    $post->update([
        'metadata' => ['key' => 'value2', 'new_key' => 'new_value'],
    ]);

    $post->refresh();

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->assertOk();

    // Verify the version contents stored the JSON correctly
    $latestVersion = $post->latestVersion;
    expect($latestVersion->contents)->toHaveKey('metadata');
});

it('can navigate back and forth between versions', function () {
    $post = createPostWithVersions($this->user, 5);

    $versions = $post->versions()->orderBy('id', 'desc')->get();
    $latest = $versions[0];
    $prev1 = $versions[1];
    $prev2 = $versions[2];

    livewire(PostRevisions::class, ['record' => $post->getKey()])
        ->assertSet('version.id', $latest->id)
        // Go back twice
        ->callAction('previousVersion')
        ->assertSet('version.id', $prev1->id)
        ->callAction('previousVersion')
        ->assertSet('version.id', $prev2->id)
        // Go forward twice
        ->callAction('nextVersion')
        ->assertSet('version.id', $prev1->id)
        ->callAction('nextVersion')
        ->assertSet('version.id', $latest->id);
});

it('shows the page breadcrumb', function () {
    $post = createPostWithVersions($this->user);

    $component = livewire(PostRevisions::class, ['record' => $post->getKey()]);

    expect($component->instance()->getBreadcrumb())
        ->toBe(__('filament-versionable::page.breadcrumb'));
});

it('shows the content tab label', function () {
    $post = createPostWithVersions($this->user);

    $component = livewire(PostRevisions::class, ['record' => $post->getKey()]);

    expect($component->instance()->getContentTabLabel())
        ->toBe(__('filament-versionable::page.content_tab_label'));
});

it('returns correct navigation icon', function () {
    expect(PostRevisions::getNavigationIcon())->toBe('heroicon-o-clock');
});

it('returns default revisions list per page value', function () {
    $post = createPostWithVersions($this->user);

    $component = livewire(PostRevisions::class, ['record' => $post->getKey()]);

    expect($component->instance()->getRevisionsListPerPage())->toBe(10);
});

it('does not strip tags by default', function () {
    $post = createPostWithVersions($this->user);

    $component = livewire(PostRevisions::class, ['record' => $post->getKey()]);

    expect($component->instance()->shouldStripTags())->toBeFalse();
});

function createPostWithVersions(User $user, int $versionCount = 3): Post
{
    $post = Post::create([
        'title' => 'Version 1 Title',
        'content' => 'Version 1 Content',
        'user_id' => $user->id,
    ]);

    for ($i = 2; $i <= $versionCount; $i++) {
        $post->update([
            'title' => "Version {$i} Title",
            'content' => "Version {$i} Content",
        ]);
    }

    return $post->refresh();
}
