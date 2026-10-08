<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = ['attachable_id', 'attachable_type', 'name'];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
