<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsAdmin(?User $admin = null): User
    {
        $admin ??= User::factory()->admin()->create();

        $this->withSession(['admin_authenticated' => true])->actingAs($admin);

        return $admin;
    }
}
