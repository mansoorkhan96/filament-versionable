<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Overtrue\LaravelVersionable\VersionStrategy;

/**
 * A VersionablePost variant that uses the per-relation configuration
 * notation of HasVersionableRelations (title / fields / identity /
 * preserve_ids). Reuses the same tables as VersionablePost.
 */
class ConfiguredVersionablePost extends VersionablePost
{
    protected $fillable = ['title', 'content', 'user_id', 'category_id'];

    protected $versionable = ['title', 'content', 'category_id'];

    protected $versionStrategy = VersionStrategy::SNAPSHOT;

    protected $versionableRelations = [
        'comments' => [
            'title' => 'author',
            'fields' => ['body'],
            'identity' => ['author'],
        ],
        'tags' => [
            'title' => [self::class, 'tagTitle'],
            'identity' => ['name'],
        ],
        'attachments' => [
            'preserve_ids' => false,
        ],
        'category',
    ];

    public static function tagTitle(array $attributes, int|string $key): string
    {
        return 'TAG: '.strval($attributes['name'] ?? $key);
    }
}
