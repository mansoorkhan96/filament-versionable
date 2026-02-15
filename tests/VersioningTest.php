<?php

use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Post;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PostResource\Pages\PostRevisions;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = createUser();
    $this->actingAs($this->user);
});

describe('version restoration', function () {
    it('can restore to a previous version', function () {
        $post = Post::create([
            'title' => 'Original Title',
            'content' => 'Original Content',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Updated Title', 'content' => 'Updated Content']);
        $post->refresh();

        expect($post->title)->toBe('Updated Title');

        // Restore via the revisions page
        livewire(PostRevisions::class, ['record' => $post->getKey()])
            ->callAction('restoreVersion')
            ->assertRedirect();

        $post->refresh();
        expect($post->title)->toBe('Original Title');
        expect($post->content)->toBe('Original Content');
    });

    it('restores the correct version when navigating to a specific version first', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Version 2', 'content' => 'Content 2']);
        $post->update(['title' => 'Version 3', 'content' => 'Content 3']);
        $post->update(['title' => 'Version 4', 'content' => 'Content 4']);
        $post->refresh();

        $versions = $post->versions()->orderBy('id', 'asc')->get();
        $version3 = $versions[2]; // Third version

        // Navigate to version 3, then restore (restores previous = version 2)
        livewire(PostRevisions::class, ['record' => $post->getKey()])
            ->call('showVersion', $version3->getKey())
            ->callAction('restoreVersion')
            ->assertRedirect();

        $post->refresh();
        expect($post->title)->toBe('Version 2');
        expect($post->content)->toBe('Content 2');
    });

    it('redirects to edit page after restoring', function () {
        $post = Post::create([
            'title' => 'Original',
            'content' => 'Content',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Updated']);
        $post->refresh();

        livewire(PostRevisions::class, ['record' => $post->getKey()])
            ->callAction('restoreVersion')
            ->assertRedirect();
    });
});

it('restores json metadata correctly', function () {
    $originalMetadata = ['status' => 'draft', 'tags' => ['php', 'laravel']];

    $post = Post::create([
        'title' => 'Title',
        'content' => 'Content',
        'metadata' => $originalMetadata,
        'user_id' => $this->user->id,
    ]);

    $post->update(['metadata' => ['status' => 'published']]);
    $post->refresh();

    expect($post->metadata)->toBe(['status' => 'published']);

    // Revert to previous version
    $post->revertToVersion($post->firstVersion->id);
    $post->refresh();

    expect($post->metadata)->toBe($originalMetadata);
});
