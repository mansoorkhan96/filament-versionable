<?php

use Illuminate\Database\Eloquent\Model;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Page;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Post;

beforeEach(function () {
    $this->user = createUser();
    $this->actingAs($this->user);
});

it('does not throw when creating a versionable model without user_id column', function () {
    Model::preventAccessingMissingAttributes();

    $page = Page::create([
        'title' => 'Test Page',
        'slug' => 'test-page',
        'content' => 'Some content',
    ]);

    expect($page)->toBeInstanceOf(Page::class);
    expect($page->versions)->toHaveCount(1);
    expect($page->versions->first()->user_id)->toBe($this->user->id);
});

it('falls back to auth id when model has no user_id attribute', function () {
    $page = Page::make(['title' => 'Test', 'slug' => 'test', 'content' => 'c']);

    expect($page->getVersionUserId())->toBe($this->user->id);
});

it('returns model user_id when the attribute is present', function () {
    $page = Page::make(['title' => 'Test', 'slug' => 'test', 'content' => 'c']);
    $page->user_id = 42;

    expect($page->getVersionUserId())->toBe(42);
});

it('stores model user_id in version for models with user_id column', function () {
    $otherUser = createUser(['email' => 'other@example.com', 'name' => 'Other User']);

    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Some content',
        'user_id' => $otherUser->id,
    ]);

    expect($post->versions)->toHaveCount(1);
    expect($post->versions->first()->user_id)->toBe($otherUser->id);
});

it('returns null user_id when no user is authenticated and model has no user_id', function () {
    auth()->logout();

    $page = Page::make(['title' => 'Test', 'slug' => 'test', 'content' => 'c']);

    expect($page->getVersionUserId())->toBeNull();
});
