<?php

namespace Mansoor\FilamentVersionable\Concerns;

/**
 * Opt-in concern that enables relationship versioning for a Versionable model.
 *
 * Declare the relationships you want versioned — either as a flat list of
 * relation names or with per-relation configuration:
 *
 *   use Mansoor\FilamentVersionable\Concerns\HasVersionableRelations;
 *
 *   protected array $versionableRelations = ['comments', 'tags'];
 *
 *   protected array $versionableRelations = [
 *       'comments' => [
 *           // Shown on the revisions page instead of the bare primary key.
 *           'title'  => 'author', // attribute name or fn (array $attributes, int|string $key): string
 *           // Snapshot attributes rendered on the revisions page (default: all).
 *           'fields' => ['author', 'body'],
 *           // Stable columns used to re-identify a row on restore when its
 *           // primary key no longer matches (deleted and re-created rows,
 *           // restores into another environment, ...).
 *           'identity' => ['author'],
 *           // Re-create missing children with their original primary key
 *           // whenever that key is still free (default: true).
 *           'preserve_ids' => true,
 *       ],
 *       'tags',
 *   ];
 *
 * Every created version stores a snapshot of these relationships, and
 * restoring a version automatically restores the relationship state.
 */
trait HasVersionableRelations
{
    public function usesVersionableRelations(): bool
    {
        return filled($this->getVersionableRelations());
    }

    /**
     * The names of the versioned relationships, from both the flat and the
     * configured notation.
     *
     * @return list<string>
     */
    public function getVersionableRelations(): array
    {
        if (! property_exists($this, 'versionableRelations')) {
            return [];
        }

        $names = [];

        foreach ((array) $this->versionableRelations as $key => $value) {
            if (is_int($key) && is_string($value) && filled($value)) {
                $names[] = $value;
            } elseif (is_string($key) && filled($key)) {
                $names[] = $key;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * The resolved configuration for a single versioned relationship,
     * merged over the plugin defaults.
     *
     * @return array{title: string|callable|null, fields: list<string>|callable|null, identity: list<string>, preserve_ids: bool}
     */
    public function getVersionableRelationConfig(string $name): array
    {
        $config = [];

        if (property_exists($this, 'versionableRelations')) {
            foreach ((array) $this->versionableRelations as $key => $value) {
                $relationName = is_int($key) ? $value : $key;

                if ($relationName === $name && is_array($value)) {
                    $config = $value;

                    break;
                }
            }
        }

        return array_merge([
            'title' => null,
            'fields' => null,
            'identity' => [],
            'preserve_ids' => true,
        ], $config);
    }
}
