<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    protected $fillable = ['name', 'email', 'password', 'versionable_post_id'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return null;
    }
}
