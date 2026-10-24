<?php

namespace Mansoor\FilamentVersionable\Support;

use Illuminate\Database\Eloquent\Model;
use Jfcherng\Diff\Differ;
use Jfcherng\Diff\DiffHelper;
use Overtrue\LaravelVersionable\Version;

/**
 * Renders the diff between a version and its predecessor, with first-class
 * support for models using spatie/laravel-translatable.
 *
 * Translatable attributes are stored in version snapshots as raw JSON strings
 * (e.g. {"en":"Hello","ar":"مرحبا"}). This class decodes them and produces one
 * diff block per locale instead of a single unreadable JSON blob, while every
 * other attribute keeps its original rendering.
 *
 * Detection is duck-typed on `getTranslatableAttributes()`, so no hard
 * dependency on spatie/laravel-translatable is introduced.
 */
class TranslatableDiff
{
    /**
     * Languages written right-to-left, matched against the base language subtag.
     */
    protected const RTL_LANGUAGES = [
        'ar', 'arc', 'ckb', 'dv', 'fa', 'he', 'khw', 'ks', 'ps', 'sd', 'ug', 'ur', 'yi',
    ];

    /**
     * @var list<string>
     */
    protected array $translatableAttributes;

    /**
     * @param  array<string, mixed>  $differOptions
     * @param  array<string, mixed>  $renderOptions
     */
    final public function __construct(
        protected Version $version,
        protected array $differOptions = [],
        protected array $renderOptions = [],
        protected bool $stripTags = false,
    ) {
        $this->translatableAttributes = static::resolveTranslatableAttributes($version);
    }

    /**
     * @param  array<string, mixed>  $differOptions
     * @param  array<string, mixed>  $renderOptions
     */
    public static function forVersion(
        Version $version,
        array $differOptions = [],
        array $renderOptions = [],
        bool $stripTags = false,
    ): static {
        return new static($version, $differOptions, $renderOptions, $stripTags);
    }

    /**
     * The structured diff entries, with translatable attributes expanded per locale.
     *
     * @return list<DiffEntry>
     */
    public function entries(): array
    {
        $diff = $this->version->diff();

        $renderedHtml = $diff->toSideBySideHtml(
            $this->differOptions,
            $this->renderOptions,
            $this->stripTags,
        );

        $entries = [];

        foreach ($this->rawPairs() as $field => $pair) {
            if (in_array($field, $this->translatableAttributes, true)) {
                foreach ($this->expandLocalePair($pair) as $expanded) {
                    $entries[] = new DiffEntry(
                        field: $field,
                        locale: $expanded['locale'],
                        localeLabel: static::localeLabel($expanded['locale']),
                        direction: static::localeDirection($expanded['locale']),
                        html: $this->renderLocaleDiff($expanded),
                    );
                }

                continue;
            }

            $entries[] = new DiffEntry(
                field: $field,
                locale: null,
                localeLabel: '',
                direction: 'auto',
                html: $renderedHtml[$field] ?? '',
            );
        }

        return $entries;
    }

    /**
     * Aggregate diff statistics, mirroring the algorithm used by
     * `Overtrue\LaravelVersionable\Diff::getStatistics()` but computed
     * over the locale-expanded pairs.
     *
     * @return array{inserted: int, deleted: int, unmodified: int, changedRatio: float|int}
     */
    public function statistics(): array
    {
        if (empty($this->translatableAttributes)) {
            return $this->version->diff()->getStatistics($this->differOptions);
        }

        $inserted = 0;
        $deleted = 0;
        $unmodified = 0;
        $changedRatio = 0;

        foreach ($this->localeExpandedPairs() as $pair) {
            $old = $pair['old'] ?? null;
            $new = $pair['new'] ?? null;

            if ($old === null) {
                $inserted += is_string($new)
                    ? substr_count($new, "\n") + 1
                    : 1;

                continue;
            }

            if ($new === $old) {
                continue;
            }

            $stats = (new Differ(
                explode("\n", static::stringify($old, forLocale: false)),
                explode("\n", static::stringify($new, forLocale: false)),
                $this->differOptions,
            ))->getStatistics();

            $inserted += $stats['inserted'];
            $deleted += $stats['deleted'];
            $unmodified += $stats['unmodified'];
            $changedRatio += $stats['changedRatio'];
        }

        return [
            'inserted' => $inserted,
            'deleted' => $deleted,
            'unmodified' => $unmodified,
            'changedRatio' => $changedRatio,
        ];
    }

    /**
     * Resolve the translatable attributes of the versioned model, if any.
     *
     * @return list<string>
     */
    public static function resolveTranslatableAttributes(Version $version): array
    {
        $versionable = $version->versionable;

        if (! $versionable instanceof Model) {
            return [];
        }

        if (! method_exists($versionable, 'getTranslatableAttributes')) {
            return [];
        }

        $attributes = (array) $versionable->getTranslatableAttributes();

        return array_values(array_filter($attributes, is_string(...)));
    }

    /**
     * A human-readable label for a locale, e.g. "en" => "English".
     *
     * Falls back to the upper-cased locale code when intl is unavailable
     * or the locale cannot be resolved.
     */
    public static function localeLabel(?string $locale): string
    {
        if ($locale === null) {
            return '';
        }

        $label = null;

        if (function_exists('locale_get_display_name')) {
            $label = locale_get_display_name($locale, app()->getLocale()) ?: null;
        }

        if ($label === null || strcasecmp($label, $locale) === 0) {
            return strtoupper($locale);
        }

        return $label;
    }

    /**
     * A text direction hint for a locale ("ltr", "rtl" or "auto").
     */
    public static function localeDirection(?string $locale): string
    {
        if ($locale === null) {
            return 'auto';
        }

        $language = strtolower(str_replace('_', '-', $locale));
        $language = explode('-', $language)[0];

        return in_array($language, static::RTL_LANGUAGES, true) ? 'rtl' : 'ltr';
    }

    /**
     * The raw old/new value pairs for each changed attribute, resolved the
     * same way `Overtrue\LaravelVersionable\Diff` resolves them (including
     * the DIFF version strategy merge).
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    protected function rawPairs(): array
    {
        return $this->version->diff()->toArray(
            $this->differOptions,
            $this->renderOptions,
            $this->stripTags,
        );
    }

    /**
     * The old/new pairs with translatable attributes expanded into one pair per locale.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    protected function localeExpandedPairs(): array
    {
        $expanded = [];

        foreach ($this->rawPairs() as $field => $pair) {
            if (! in_array($field, $this->translatableAttributes, true)) {
                $expanded[$field] = $pair;

                continue;
            }

            foreach ($this->expandLocalePair($pair) as $expandedPair) {
                $expanded[sprintf('%s#%s', $field, $expandedPair['locale'] ?? 'raw')] = [
                    'old' => $expandedPair['old'],
                    'new' => $expandedPair['new'],
                ];
            }
        }

        return $expanded;
    }

    /**
     * Expand a single attribute's old/new pair into one pair per locale.
     *
     * Falls back to the raw pair (with a null locale) when the values cannot
     * be decoded as translations on both sides, e.g. corrupt or legacy data.
     *
     * @param  array{old: mixed, new: mixed}  $pair
     * @return list<array{locale: string|null, old: mixed, new: mixed}>
     */
    protected function expandLocalePair(array $pair): array
    {
        $old = $pair['old'] ?? null;
        $new = $pair['new'] ?? null;

        $oldTranslations = static::decodeTranslations($old);
        $newTranslations = static::decodeTranslations($new);

        // Both sides must either be decodable translations or absent
        // (null), otherwise the raw diff is the honest representation.
        if ((! is_array($oldTranslations) && $old !== null)
            || (! is_array($newTranslations) && $new !== null)
        ) {
            return [[
                'locale' => null,
                'old' => $old,
                'new' => $new,
            ]];
        }

        $locales = array_values(array_unique(array_merge(
            array_keys($oldTranslations ?? []),
            array_keys($newTranslations ?? []),
        )));

        $expanded = [];

        foreach ($locales as $locale) {
            $expanded[] = [
                'locale' => (string) $locale,
                'old' => $oldTranslations[$locale] ?? null,
                'new' => $newTranslations[$locale] ?? null,
            ];
        }

        return $expanded;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function decodeTranslations(mixed $value): ?array
    {
        if (! is_string($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Render one locale pair as a side-by-side diff, using the exact same
     * renderer and options the revisions page already uses.
     *
     * @param  array{locale: string|null, old: mixed, new: mixed}  $pair
     */
    protected function renderLocaleDiff(array $pair): string
    {
        $old = static::stringify($pair['old'] ?? null);
        $new = static::stringify($pair['new'] ?? null);

        $html = str_replace(
            '\n No newline at end of file',
            '',
            DiffHelper::calculate($old, $new, 'SideBySide', $this->differOptions, $this->renderOptions),
        );

        return static::localeDirection($pair['locale']) === 'rtl'
            ? static::isolateMarkup($html)
            : $html;
    }

    /**
     * Isolate escaped HTML tags (e.g. `&lt;p&gt;`) as left-to-right runs so
     * they are not mirrored inside right-to-left text. A tag may contain
     * whole `<ins>`/`<del>` highlights; any other markup inside it leaves
     * the tag untouched so the rendered HTML stays well-formed.
     */
    protected static function isolateMarkup(string $html): string
    {
        return preg_replace(
            '/&lt;\/?[a-zA-Z](?:[^<&]|&(?!lt;|gt;)|<(ins|del)>[^<]*<\/\1>)*?&gt;/',
            '<bdi dir="ltr">$0</bdi>',
            $html,
        ) ?? $html;
    }

    /**
     * Normalize a locale value for diffing/rendering.
     *
     * Locale values are rendered with unescaped unicode so that non-latin
     * content stays readable. Non-locale values keep the exact
     * `json_encode` behavior of the underlying diff package.
     */
    protected static function stringify(mixed $value, bool $forLocale = true): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return $value;
        }

        if ($forLocale) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return json_encode($value) ?: '';
    }
}
