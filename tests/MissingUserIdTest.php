<?php

use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Page;

beforeEach(function () {
    $this->user = createUser();
    $this->actingAs($this->user);
});

// This test demonstrates the MissingAttributeException bug:
// When a model uses the Versionable trait but does NOT have a `user_id` column
// in its database schema, and Model::preventAccessingMissingAttributes() is enabled,
// the Versionable trait's getVersionUserId() method calls getAttribute('user_id')
// which throws MissingAttributeException instead of returning null.
//
// This test is expected to fail until the fix is applied.
it('throws MissingAttributeException when creating a versionable model without user_id column', function () {
    Model::preventAccessingMissingAttributes();

    Page::create([
        'title' => 'Test Page',
        'slug' => 'test-page',
        'content' => 'Some content',
    ]);
})->throws(MissingAttributeException::class);
