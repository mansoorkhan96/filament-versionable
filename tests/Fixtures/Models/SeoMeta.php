<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $fillable = ['metaable_id', 'metaable_type', 'meta'];

    public function metaable(): MorphTo
    {
        return $this->morphTo();
    }
}
