<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    protected $fillable = ['name'];

    public function versionablePosts(): MorphToMany
    {
        return $this->morphToMany(VersionablePost::class, 'taggable');
    }
}
