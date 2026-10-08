<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = ['versionable_post_id', 'bio'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(VersionablePost::class, 'versionable_post_id');
    }
}
