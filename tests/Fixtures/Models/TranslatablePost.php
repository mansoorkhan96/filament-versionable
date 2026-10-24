<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelVersionable\Versionable;
use Overtrue\LaravelVersionable\VersionStrategy;
use Spatie\Translatable\HasTranslations;

class TranslatablePost extends Model
{
    use HasTranslations;
    use Versionable;

    protected $fillable = ['title', 'summary', 'status', 'user_id'];

    protected $versionable = ['title', 'summary', 'status'];

    protected $versionStrategy = VersionStrategy::SNAPSHOT;

    public array $translatable = ['title', 'summary'];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (TranslatablePost $post) {
            $post->user_id = $post->user_id ?? auth()->id();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
