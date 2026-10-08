<?php

namespace Mansoor\FilamentVersionable\Support;

use Illuminate\Support\Str;

class RelationChangeSet
{
    /**
     * @param  string  $name  the relation name
     * @param  string  $type  the Eloquent relation type key (hasMany, belongsToMany, ...)
     * @param  bool  $displayOnly  recorded for reference, never restored
     * @param  list<RelationRecordChange>  $added
     * @param  list<RelationRecordChange>  $removed
     * @param  list<RelationRecordChange>  $updated
     * @param  array<string, array<string, mixed>>|null  $oldState
     * @param  array<string, array<string, mixed>>|null  $newState
     * @param  int  $unchanged  number of untouched records
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $displayOnly = false,
        public array $added = [],
        public array $removed = [],
        public array $updated = [],
        public int $unchanged = 0,
        public ?array $oldState = null,
        public ?array $newState = null,
    ) {}

    public function hasChanges(): bool
    {
        return $this->added !== [] || $this->removed !== [] || $this->updated !== [];
    }

    public function label(): string
    {
        return Str::headline($this->name);
    }

    public function typeLabel(): string
    {
        return Str::headline($this->type);
    }
}
