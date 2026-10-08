<?php

namespace Mansoor\FilamentVersionable\Support;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Version;

/**
 * Resolves the per-relation display configuration and computes the
 * human-facing label of a snapshotted related record.
 *
 * Without any configuration a sensible title is guessed from common
 * attribute names so rows never show a naked primary key.
 */
class RelationDisplay
{
    /**
     * Attribute candidates used to guess a record title, tried in order.
     *
     * @var list<string>
     */
    protected const DEFAULT_TITLE_CANDIDATES = ['name', 'title', 'label', 'author', 'email', 'username', 'slug'];

    /**
     * The display configuration for a versioned relation of the version's
     * model, falling back to plugin defaults when the model is unknown.
     *
     * @return array{title: string|callable|null, fields: list<string>|callable|null, identity: list<string>, preserve_ids: bool}
     */
    public static function configFor(Version $version, string $relationName): array
    {
        $record = $version->versionable;

        if ($record instanceof Model && method_exists($record, 'getVersionableRelationConfig')) {
            return $record->getVersionableRelationConfig($relationName);
        }

        return [
            'title' => null,
            'fields' => null,
            'identity' => [],
            'preserve_ids' => true,
        ];
    }

    /**
     * Human label for a snapshotted record: "#<key> · <title>".
     *
     * @param  array{title: string|callable|null, fields: list<string>|callable|null, identity: list<string>, preserve_ids: bool}  $config
     * @param  array<string, mixed>  $attributes
     */
    public static function label(array $config, array $attributes, int|string $key): string
    {
        $label = '#'.$key;

        $title = static::title($config, $attributes, $key);

        return $title === null ? $label : $label.' · '.$title;
    }

    /**
     * Resolve the record title: the configured attribute (or closure result)
     * first, then the default attribute candidates.
     *
     * @param  array{title: string|callable|null, fields: list<string>|callable|null, identity: list<string>, preserve_ids: bool}  $config
     * @param  array<string, mixed>  $attributes
     */
    protected static function title(array $config, array $attributes, int|string $key): ?string
    {
        $configured = $config['title'] ?? null;

        if ($configured !== null) {
            $value = is_callable($configured)
                ? $configured($attributes, $key)
                : ($attributes[$configured] ?? null);

            if (is_scalar($value) && trim(strval($value)) !== '') {
                return strval($value);
            }
        }

        foreach (self::DEFAULT_TITLE_CANDIDATES as $candidate) {
            $value = $attributes[$candidate] ?? null;

            if (is_scalar($value) && trim(strval($value)) !== '') {
                return strval($value);
            }
        }

        return null;
    }

    /**
     * The subset of snapshot attributes to render on the revisions page,
     * or null when every attribute should be rendered.
     *
     * @param  array{title: string|callable|null, fields: list<string>|callable|null, identity: list<string>, preserve_ids: bool}  $config
     * @return list<string>|null
     */
    public static function fields(array $config): ?array
    {
        $fields = $config['fields'] ?? null;

        if (is_callable($fields)) {
            $fields = $fields();
        }

        if (! is_array($fields)) {
            return null;
        }

        return array_values(array_filter($fields, is_string(...)));
    }
}
