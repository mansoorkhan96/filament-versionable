<?php

use Mansoor\FilamentVersionable\Tests\Fixtures\Models\User;
use Mansoor\FilamentVersionable\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

function createUser(array $attributes = []): User
{
    return User::create(array_merge([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ], $attributes));
}
