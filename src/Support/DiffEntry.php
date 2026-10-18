<?php

namespace Mansoor\FilamentVersionable\Support;

/**
 * A single rendered diff block for one attribute (or one locale of a
 * translatable attribute) between two versions.
 */
class DiffEntry
{
    /**
     * @param  string  $field  The versioned attribute name.
     * @param  string|null  $locale  The locale this entry belongs to, or null for non-translatable attributes.
     * @param  string  $localeLabel  Human-readable locale label (e.g. "Arabic"), empty for non-translatable attributes.
     * @param  string  $direction  Text direction of the locale's diff cell contents ("ltr", "rtl" or "auto").
     * @param  string  $html  The rendered side-by-side diff HTML.
     */
    public function __construct(
        public readonly string $field,
        public readonly ?string $locale,
        public readonly string $localeLabel,
        public readonly string $direction,
        public readonly string $html,
    ) {}

    /**
     * The array key used by the legacy `diff()` computed property.
     */
    public function key(): string
    {
        return $this->locale === null
            ? $this->field
            : sprintf('%s (%s)', $this->field, $this->locale);
    }
}
