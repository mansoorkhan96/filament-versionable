<?php

use Illuminate\Database\Eloquent\Model;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Page;

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
