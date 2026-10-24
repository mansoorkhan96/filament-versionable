<?php

use Mansoor\FilamentVersionable\Support\DiffEntry;
use Mansoor\FilamentVersionable\Support\TranslatableDiff;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\DiffTranslatablePost;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Post;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\TranslatablePost;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\PostResource\Pages\PostRevisions;
use Mansoor\FilamentVersionable\Tests\Fixtures\Resources\TranslatablePostResource\Pages\TranslatablePostRevisions;
use Overtrue\LaravelVersionable\Version;

beforeEach(function () {
    $this->user = createUser();
    $this->actingAs($this->user);
});

describe('translatable attribute detection', function () {
    it('resolves translatable attributes from the versioned model', function () {
        $post = createTranslatablePost();

        $attributes = TranslatableDiff::resolveTranslatableAttributes($post->latestVersion);

        expect($attributes)->toBe(['title', 'summary']);
    });

    it('resolves no translatable attributes for regular models', function () {
        $post = createUserPost();

        expect(TranslatableDiff::resolveTranslatableAttributes($post->latestVersion))->toBe([]);
    });

    it('resolves no translatable attributes when versionable is missing', function () {
        $post = createTranslatablePost();

        $version = $post->latestVersion;
        $version->setRelation('versionable', null);

        expect(TranslatableDiff::resolveTranslatableAttributes($version))->toBe([]);
    });
});

describe('per-locale diff entries', function () {
    it('expands translatable attributes into one entry per locale', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, [
            'title' => ['en' => 'Updated Title'],
        ]);

        $entries = TranslatableDiff::forVersion($post->latestVersion)->entries();

        $titleEntries = array_values(array_filter(
            $entries,
            fn (DiffEntry $entry) => $entry->field === 'title',
        ));

        expect($titleEntries)->toHaveCount(3);
        expect($titleEntries[0]->locale)->toBe('en');
        expect($titleEntries[1]->locale)->toBe('ar');
        expect($titleEntries[2]->locale)->toBe('fr');
    });

    it('keeps non-translatable attributes as a single entry', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, ['status' => 'published']);

        $entries = TranslatableDiff::forVersion($post->latestVersion)->entries();

        $statusEntries = array_values(array_filter(
            $entries,
            fn (DiffEntry $entry) => $entry->field === 'status',
        ));

        expect($statusEntries)->toHaveCount(1);
        expect($statusEntries[0]->locale)->toBeNull();
        expect($statusEntries[0]->html)->toContain('published');
    });

    it('renders the changed locale as a word diff', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, [
            'title' => ['en' => 'Updated Title'],
        ]);

        $entries = pageDiff($post->latestVersion)->entries();

        $englishEntry = collect($entries)->first(
            fn (DiffEntry $entry) => $entry->field === 'title' && $entry->locale === 'en',
        );

        expect($englishEntry->html)->toContain('Original');
        expect($englishEntry->html)->toContain('Updated');
        expect($englishEntry->html)->not->toContain('{"en"');
    });

    it('renders unchanged locales as identical context', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, [
            'title' => ['en' => 'Updated Title'],
        ]);

        $entries = pageDiff($post->latestVersion)->entries();

        $arabicEntry = collect($entries)->first(
            fn (DiffEntry $entry) => $entry->field === 'title' && $entry->locale === 'ar',
        );

        expect($arabicEntry->html)->toContain('عنوان تجريبي');
        expect($arabicEntry->html)->not->toContain('{"en"');
    });

    it('shows locales added in later versions as insertions', function () {
        $post = createTranslatablePost(withFrench: false);
        updateTranslatablePost($post, [
            'title' => ['fr' => 'Titre mis à jour'],
        ]);

        $entries = TranslatableDiff::forVersion($post->latestVersion)->entries();

        $frenchEntry = collect($entries)->first(
            fn (DiffEntry $entry) => $entry->field === 'title' && $entry->locale === 'fr',
        );

        expect($frenchEntry)->not->toBeNull();
        expect($frenchEntry->html)->toContain('Titre mis à jour');
    });

    it('shows locales removed in later versions as deletions', function () {
        $post = createTranslatablePost();
        $post->forgetTranslation('title', 'fr');
        $post->save();

        $entries = pageDiff($post->latestVersion)->entries();

        $frenchEntry = collect($entries)->first(
            fn (DiffEntry $entry) => $entry->field === 'title' && $entry->locale === 'fr',
        );

        expect($frenchEntry)->not->toBeNull();
        expect($frenchEntry->html)->toContain('<td class="old"><del>Titre original</del></td>');
    });

    it('isolates html tags in right-to-left locales only', function () {
        $post = TranslatablePost::create([
            'title' => ['en' => 'Title', 'ar' => 'عنوان'],
            'summary' => ['en' => '<p>Hello</p>', 'ar' => '<p>مرحبا</p>'],
            'user_id' => auth()->id(),
        ]);
        updateTranslatablePost($post, [
            'summary' => ['en' => '<p class="lead">Hello all</p>', 'ar' => '<p class="lead">مرحبا بكم</p>'],
        ]);

        $entries = collect(pageDiff($post->latestVersion)->entries());

        $arabicHtml = $entries->first(fn (DiffEntry $entry) => $entry->field === 'summary' && $entry->locale === 'ar')->html;
        $englishHtml = $entries->first(fn (DiffEntry $entry) => $entry->field === 'summary' && $entry->locale === 'en')->html;

        expect($arabicHtml)
            ->toContain('<bdi dir="ltr">&lt;p&gt;</bdi>')
            ->toContain('<bdi dir="ltr">&lt;p<ins> class="lead"</ins>&gt;</bdi>')
            ->toContain('<bdi dir="ltr">&lt;/p&gt;</bdi>');
        expect($englishHtml)->not->toContain('<bdi');
    });

    it('marks right-to-left locales with rtl direction', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, [
            'title' => ['en' => 'Updated Title'],
        ]);

        $entries = TranslatableDiff::forVersion($post->latestVersion)->entries();

        $arabicEntry = collect($entries)->first(
            fn (DiffEntry $entry) => $entry->field === 'title' && $entry->locale === 'ar',
        );
        $englishEntry = collect($entries)->first(
            fn (DiffEntry $entry) => $entry->field === 'title' && $entry->locale === 'en',
        );

        expect($arabicEntry->direction)->toBe('rtl');
        expect($englishEntry->direction)->toBe('ltr');
    });

    it('falls back to the raw diff for non-json values', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, ['status' => 'published']);

        $version = $post->latestVersion;
        $contents = $version->contents;
        $contents['title'] = 'legacy plain string';
        $version->update(['contents' => $contents]);

        $entries = TranslatableDiff::forVersion($version->refresh())->entries();

        $titleEntries = array_values(array_filter(
            $entries,
            fn (DiffEntry $entry) => $entry->field === 'title',
        ));

        expect($titleEntries)->toHaveCount(1);
        expect($titleEntries[0]->locale)->toBeNull();
        expect($titleEntries[0]->html)->toContain('legacy plain string');
    });

    it('supports the DIFF version strategy for translatable models', function () {
        $post = DiffTranslatablePost::create([
            'title' => ['en' => 'Original Title', 'ar' => 'عنوان تجريبي'],
            'summary' => ['en' => 'Original summary'],
            'status' => 'draft',
            'user_id' => auth()->id(),
        ]);

        updateTranslatablePost($post, ['title' => ['en' => 'Updated Title']]);

        $versions = $post->versions()->orderBy('id')->get();
        $secondVersion = $versions[1];

        $entries = pageDiff($secondVersion)->entries();

        $titleEntries = array_values(array_filter(
            $entries,
            fn (DiffEntry $entry) => $entry->field === 'title',
        ));

        expect($titleEntries)->toHaveCount(2);

        $englishEntry = collect($titleEntries)->first(fn (DiffEntry $entry) => $entry->locale === 'en');

        expect($englishEntry->html)->toContain('Original');
        expect($englishEntry->html)->toContain('Updated');
        expect($englishEntry->html)->not->toContain('{"en"');
    });
});

describe('backward compatibility', function () {
    it('keeps one entry per field for non-translatable models', function () {
        $post = createUserPost();
        $post->update(['title' => 'Updated Title', 'content' => 'Updated Content']);

        $entries = pageDiff($post->latestVersion)->entries();

        expect($entries)->toHaveCount(3);

        foreach ($entries as $entry) {
            expect($entry->locale)->toBeNull();
            expect($entry->direction)->toBe('auto');
        }

        expect(collect($entries)->pluck('field')->all())->toBe(['title', 'content', 'metadata']);
    });

    it('delegates statistics to the underlying diff for non-translatable models', function () {
        $post = createUserPost();
        $post->update(['title' => 'Updated Title', 'content' => 'Updated Content']);

        $stats = TranslatableDiff::forVersion($post->latestVersion)->statistics();

        expect($stats)->toBe($post->latestVersion->diff()->getStatistics());
    });

    it('exposes the legacy keyed diff array on the page', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, ['status' => 'published']);

        $component = livewire(TranslatablePostRevisions::class, ['record' => $post->getKey()]);

        $diff = $component->instance()->diff;

        expect($diff)->toBeArray();
        expect($diff)->toHaveKey('status');
        expect($diff)->toHaveKey('title (en)');
        expect($diff)->toHaveKey('title (ar)');
    });
});

describe('diff statistics', function () {
    it('aggregates statistics across locale entries', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, [
            'title' => ['en' => 'Updated Title'],
            'status' => 'published',
        ]);

        $stats = TranslatableDiff::forVersion($post->latestVersion)->statistics();

        expect($stats['inserted'])->toBe(2);
        expect($stats['deleted'])->toBe(2);
    });

    it('counts changed lines per locale instead of per json blob', function () {
        $post = TranslatablePost::create([
            'title' => ['en' => 'Title'],
            'summary' => ['en' => "one\ntwo\nthree"],
            'status' => 'draft',
            'user_id' => auth()->id(),
        ]);
        updateTranslatablePost($post, [
            'summary' => ['en' => "one\nTWO\nTHREE"],
        ]);

        $stats = TranslatableDiff::forVersion($post->latestVersion)->statistics();

        expect($stats['inserted'])->toBe(2);
        expect($stats['deleted'])->toBe(2);
        expect($stats['unmodified'])->toBe(1);

        // The raw json blob is a single line, so it would report one change.
        expect($post->latestVersion->diff()->getStatistics()['inserted'])->toBe(1);
    });

    it('reports zero changes for identical snapshots', function () {
        $post = createTranslatablePost();
        $post->refresh();

        // Append an identical snapshot manually (no versionable attribute changed).
        $identicalVersion = Version::createForModel($post);

        $stats = pageDiff($identicalVersion)->statistics();

        expect($stats['inserted'])->toBe(0);
        expect($stats['deleted'])->toBe(0);
    });
});

describe('locale helpers', function () {
    it('resolves human readable locale labels', function () {
        expect(TranslatableDiff::localeLabel('en'))->toBe('English');
        expect(TranslatableDiff::localeLabel('ar'))->toBe('Arabic');
    });

    it('falls back to the upper-cased locale code for unknown locales', function () {
        expect(TranslatableDiff::localeLabel('zzz'))->toBe('ZZZ');
        expect(TranslatableDiff::localeLabel(null))->toBe('');
    });

    it('detects rtl locales by base language', function () {
        expect(TranslatableDiff::localeDirection('ar'))->toBe('rtl');
        expect(TranslatableDiff::localeDirection('ar-EG'))->toBe('rtl');
        expect(TranslatableDiff::localeDirection('he'))->toBe('rtl');
        expect(TranslatableDiff::localeDirection('fa_IR'))->toBe('rtl');
        expect(TranslatableDiff::localeDirection('fr'))->toBe('ltr');
        expect(TranslatableDiff::localeDirection(null))->toBe('auto');
    });
});

describe('revisions page rendering', function () {
    it('renders per-locale blocks with locale badges instead of raw json', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, [
            'title' => ['en' => 'Updated Title'],
        ]);

        livewire(TranslatablePostRevisions::class, ['record' => $post->getKey()])
            ->assertOk()
            ->assertSee('title')
            ->assertSee('summary')
            ->assertSee('English')
            ->assertSee('Arabic')
            ->assertSee('Original')
            ->assertSee('Updated')
            ->assertDontSee('{"en"');
    });

    it('applies the locale direction to the diff cells without mirroring the table', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, [
            'title' => ['en' => 'Updated Title'],
        ]);

        $component = livewire(TranslatablePostRevisions::class, ['record' => $post->getKey()])
            ->assertOk()
            ->assertSeeHtml('data-locale-direction="rtl"')
            ->assertSeeHtml('data-locale-direction="ltr"');

        expect(wrapsDiffTableWithDir($component->html()))->toBeFalse();
    });

    it('does not set a direction on diff tables of non-translatable models', function () {
        $post = createUserPost();
        $post->update(['title' => 'عنوان محدث']);

        $component = livewire(PostRevisions::class, ['record' => $post->getKey()])
            ->assertOk()
            ->assertDontSeeHtml('data-locale-direction');

        expect(wrapsDiffTableWithDir($component->html()))->toBeFalse();
    });

    it('does not render raw json for translatable fields after restore', function () {
        $post = createTranslatablePost();
        updateTranslatablePost($post, [
            'title' => ['en' => 'Updated Title'],
        ]);

        livewire(TranslatablePostRevisions::class, ['record' => $post->getKey()])
            ->assertOk()
            ->callAction('restoreVersion')
            ->assertRedirect();

        $post->refresh();

        expect($post->getTranslation('title', 'en'))->toBe('Original Title');
        expect($post->getTranslation('title', 'ar'))->toBe('عنوان تجريبي');
    });
});

function pageDiff(Version $version): TranslatableDiff
{
    // The same options the revisions page passes.
    return TranslatableDiff::forVersion(
        $version,
        differOptions: ['fullContextIfIdentical' => true],
        renderOptions: ['lineNumbers' => false, 'showHeader' => false, 'detailLevel' => 'word', 'spacesToNbsp' => false],
    );
}

function wrapsDiffTableWithDir(string $html): bool
{
    // A dir attribute on the table's wrapper mirrors the old/new columns.
    return preg_match('/<div[^>]*\sdir="[^"]*"[^>]*>\s*<table class="diff-wrapper/', $html) === 1;
}

function createTranslatablePost(bool $withFrench = true): TranslatablePost
{
    $title = [
        'en' => 'Original Title',
        'ar' => 'عنوان تجريبي',
    ];

    $summary = [
        'en' => 'Original summary text',
        'ar' => 'نص ملخص تجريبي',
    ];

    if ($withFrench) {
        $title['fr'] = 'Titre original';
        $summary['fr'] = 'Résumé original';
    }

    return TranslatablePost::create([
        'title' => $title,
        'summary' => $summary,
        'status' => 'draft',
        'user_id' => auth()->id(),
    ]);
}

function updateTranslatablePost(TranslatablePost $post, array $translations): TranslatablePost
{
    foreach ($translations as $attribute => $values) {
        if (is_array($values)) {
            foreach ($values as $locale => $value) {
                $post->setTranslation($attribute, $locale, $value);
            }
        } else {
            $post->{$attribute} = $values;
        }
    }

    $post->save();
    $post->refresh();

    return $post;
}

function createUserPost(): Post
{
    return Post::create([
        'title' => 'Original Title',
        'content' => 'Original Content',
        'user_id' => auth()->id(),
    ]);
}
