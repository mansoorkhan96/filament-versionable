<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Override mountParentRecord() in nested resource page fixtures so that
 * Livewire::test() can be used for nested resources. Livewire's test helper
 * creates its own HTTP request to an internal endpoint which has no route
 * parameters, causing Filament's InteractsWithParentRecord to fail when
 * resolving the parent record from request()->route()->parameters().
 *
 * Usage in tests:
 *   NestedPostRevisions::$testParentRecord = $category;
 *   livewire(NestedPostRevisions::class, ['record' => $post->getKey()])->assertOk();
 *   NestedPostRevisions::$testParentRecord = null;
 */
trait TestableNestedPage
{
    public static ?Model $testParentRecord = null;

    public function mountParentRecord(): void
    {
        if (static::$testParentRecord) {
            $this->parentRecord = static::$testParentRecord;

            return;
        }

        parent::mountParentRecord();
    }
}
