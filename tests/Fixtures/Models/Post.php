<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Versionable;
use Overtrue\LaravelVersionable\VersionStrategy;

class Post extends Model
{
    use Versionable;

    protected $fillable = ['title', 'content', 'metadata', 'user_id', 'category_id'];

    protected $versionable = ['title', 'content', 'metadata'];

    protected $versionStrategy = VersionStrategy::SNAPSHOT;

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Post $post) {
            $post->user_id = $post->user_id ?? auth()->id();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
