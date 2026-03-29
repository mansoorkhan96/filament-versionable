<?php

namespace Mansoor\FilamentVersionable;

use Overtrue\LaravelVersionable\Versionable as BaseVersionable;

trait Versionable
{
    use BaseVersionable;

    public function getVersionUserId()
    {
        $key = $this->getUserForeignKeyName();

        if (array_key_exists($key, $this->getAttributes())) {
            return $this->getAttribute($key) ?? auth()->id();
        }

        return auth()->id();
    }
}
