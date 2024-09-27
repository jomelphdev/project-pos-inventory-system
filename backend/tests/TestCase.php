<?php

namespace Tests;

use Database\Seeders\RolesSeeder;
use Database\Seeders\StateSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public $user;
    public $authToken;
    public $organization_id;
    public $preferences;
    
    function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(StateSeeder::class);

        $this->user = $this->signIn();
        $this->organization_id = $this->user->organization_id;
        $this->preferences = create('App\Preference',
            [
                'organization_id' => $this->organization_id,
                'owner_id' => $this->user->id
            ]
        );
    }

    protected function signIn($user = null)
    {
        $user = $user ? : create('App\User');
        $user->assignRole('owner');

        $this->actingAs($user);

        return $user;
    }
}
