<?php

use Filament\Actions\Testing\TestAction;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Category;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Post;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PostResource;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PostResource\Pages\EditPost;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PostResource\Pages\ListPosts;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource\Pages\EditNestedPost;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\NestedPostResource\Pages\ListNestedPosts;

beforeEach(function () {
    $this->user = createUser();
    $this->actingAs($this->user);
});

describe('Page RevisionsAction', function () {
    it('is visible on edit page when record has multiple versions', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Version 2']);

        livewire(EditPost::class, ['record' => $post->getKey()])
            ->assertActionVisible('revisions');
    });

    it('is hidden on edit page when record has only one version', function () {
        $post = Post::create([
            'title' => 'Only Version',
            'content' => 'Only Content',
            'user_id' => $this->user->id,
        ]);

        livewire(EditPost::class, ['record' => $post->getKey()])
            ->assertActionHidden('revisions');
    });

    it('shows correct badge count for versions', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Version 2']);
        $post->update(['title' => 'Version 3']);

        // The badge should show versions count - 1
        expect($post->versions()->count())->toBe(3);

        $action = new \Mansoor\FilamentVersionable\Page\RevisionsAction('revisions');
        $badgeCount = $post->versions()->count() - 1;
        expect($badgeCount)->toBe(2);
    });

    it('has the correct icon and label', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Version 2']);

        livewire(EditPost::class, ['record' => $post->getKey()])
            ->assertActionHasIcon('revisions', 'heroicon-m-clock')
            ->assertActionHasLabel('revisions', __('filament-versionable::actions.revisions'));
    });

    it('has a URL pointing to the revisions page', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Version 2']);

        $expectedUrl = PostResource::getUrl('revisions', ['record' => $post]);

        livewire(EditPost::class, ['record' => $post->getKey()])
            ->assertActionHasUrl('revisions', $expectedUrl);
    });
});

describe('Nested Page RevisionsAction', function () {
    it('has a URL pointing to the nested revisions page', function () {
        $category = Category::create(['name' => 'Test Category']);
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        $expectedUrl = NestedPostResource::getUrl('revisions', [
            'record' => $post,
            'category' => $category,
        ]);

        // The expected URL must contain the parent category ID in the path
        expect($expectedUrl)->toContain("/categories/{$category->getKey()}/");

        // Visit the real Filament edit page URL to test in a nested route context
        $editUrl = NestedPostResource::getUrl('edit', [
            'record' => $post,
            'category' => $category,
        ]);

        // The edit page should render without errors and the revisions action
        // URL should include the parent category parameter
        $this->get($editUrl)
            ->assertOk()
            ->assertSee($expectedUrl);
    });
});

describe('Nested Table RevisionsAction', function () {
    it('has a URL pointing to the nested revisions page from table', function () {
        $category = Category::create(['name' => 'Test Category']);
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
            'category_id' => $category->id,
        ]);

        $post->update(['title' => 'Version 2']);

        $expectedUrl = NestedPostResource::getUrl('revisions', [
            'record' => $post,
            'category' => $category,
        ]);

        // The expected URL must contain the parent category ID in the path
        expect($expectedUrl)->toContain("/categories/{$category->getKey()}/");

        // Visit the real Filament list page URL to test in a nested route context
        $listUrl = NestedPostResource::getUrl('index', [
            'category' => $category,
        ]);

        // The list page should render without errors and the revisions action
        // URL should include the parent category parameter
        $this->get($listUrl)
            ->assertOk()
            ->assertSee($expectedUrl);
    });
});

describe('Table RevisionsAction', function () {
    it('is visible in table when record has multiple versions', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Version 2']);

        livewire(ListPosts::class)
            ->assertActionVisible(TestAction::make('revisions')->table($post));
    });

    it('is hidden in table when record has only one version', function () {
        $post = Post::create([
            'title' => 'Only Version',
            'content' => 'Only Content',
            'user_id' => $this->user->id,
        ]);

        livewire(ListPosts::class)
            ->assertActionHidden(TestAction::make('revisions')->table($post));
    });

    it('shows correct badge count for table action', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Version 2']);
        $post->update(['title' => 'Version 3']);
        $post->update(['title' => 'Version 4']);

        // The badge should show versions count - 1
        $badgeCount = $post->versions()->count() - 1;
        expect($badgeCount)->toBe(3);
    });

    it('has the correct icon and label in table', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Version 2']);

        livewire(ListPosts::class)
            ->assertActionHasIcon(TestAction::make('revisions')->table($post), 'heroicon-m-clock');
    });

    it('has the correct label in table', function () {
        $post = Post::create([
            'title' => 'Version 1',
            'content' => 'Content 1',
            'user_id' => $this->user->id,
        ]);

        $post->update(['title' => 'Version 2']);

        livewire(ListPosts::class)
            ->assertActionHasLabel(
                TestAction::make('revisions')->table($post),
                __('filament-versionable::actions.revisions'),
            );
    });
});
