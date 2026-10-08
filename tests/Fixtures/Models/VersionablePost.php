<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Mansoor\FilamentVersionable\Concerns\HasVersionableRelations;
use Overtrue\LaravelVersionable\Versionable;
use Overtrue\LaravelVersionable\VersionStrategy;

class VersionablePost extends Model
{
    use HasVersionableRelations;
    use Versionable;

    protected $table = 'posts';

    protected $fillable = ['title', 'content', 'user_id', 'category_id'];

    protected $versionable = ['title', 'content', 'category_id'];

    protected $versionStrategy = VersionStrategy::SNAPSHOT;

    protected $versionableRelations = [
        'comments',      // HasMany
        'profile',       // HasOne
        'tags',          // BelongsToMany (pivot position)
        'featuredTags',  // MorphToMany (pivot position)
        'attachments',   // MorphMany
        'seoMeta',       // MorphOne
        'category',      // BelongsTo (display only)
        'projects',      // HasManyThrough (display only)
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'versionable_post_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id')->withPivot('position');
    }

    public function featuredTags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable')->withPivot('position');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'metaable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function projects(): HasManyThrough
    {
        return $this->hasManyThrough(Project::class, User::class, 'versionable_post_id', 'user_id');
    }
}
