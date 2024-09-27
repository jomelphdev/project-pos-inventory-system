<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    // POST REQUESTS

    /**
     * @test
     */
    public function can_create_user()
    {
        $response = $this->post('/api/users/create', [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->safeEmail,
            'username' => $this->faker->userName,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user'
            ]
        ]);

        $data = $response->json()['data'];

        $this->assertDatabaseHas('users', ['id' => $data['user']['id']]);
    }

    /**
     * @test
     */
    public function can_create_user_with_feedback()
    {
        $response = $this->post('/api/users/create', [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->safeEmail,
            'username' => $this->faker->userName,
            'password' => 'password',
            'feedback' => make('UserFeedback', [], 3)->toArray()
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user'
            ]
        ]);

        $data = $response->json()['data'];

        $this->assertDatabaseHas('users', ['id' => $data['user']['id']]);
        $this->assertDatabaseCount('user_feedback', 3);
    }

    /**
     * @test
     */
    public function can_authenticate_user()
    {
        $user = create('User');
        create('Preference', ['organization_id' => $user->organization_id]);

        $response = $this->post('/api/users/authenticate', [
            'username' => $user->username,
            'password' => 'password'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user'
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_update_user()
    {
        $user = create('User');
        $this->actingAs($user);

        $newUsername = 'NewUsername';
        $newPassword = 'unique_code';

        $response = $this->post('/api/users/update/' . $user->id, [
            'update' => [
                'username' => $newUsername,
                'password' => $newPassword,
            ]
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user'
            ]
        ]);

        $user->refresh();

        $this->assertEquals($newUsername, $user->username);
        $this->assertTrue(Hash::check($newPassword, $user->password));
    }

    /**
     * @test
     */
    public function manager_can_update_user()
    {
        $employee = create('User', ['organization_id' => $this->organization_id]);
        $employee->assignRole('employee');
        $user = create('User', ['organization_id' => $this->organization_id]);
        $user->assignRole('manager');
        $this->actingAs($user);

        $response = $this->post('/api/users/update/' . $employee->id, [
            'update' => [
                'deleted_at' => now()
            ]
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user'
            ]
        ]);

        $this->assertSoftDeleted('users', ['id' => $employee->id]);
    }

    /**
     * @test
     */
    public function policy_blocks_from_updating()
    {
        $user = create('User');

        $newUsername = 'NewUsername';
        $newPassword = 'unique_code';

        $response = $this->post('/api/users/update/' . $user->id, [
            'update' => [
                'username' => $newUsername,
                'password' => $newPassword,
            ]
        ]);
        
        $response->assertStatus(403);
        $response->assertJsonStructure([
            'success',
            'message'
        ]);

        $this->assertFalse($user->isDirty());
    }

    /**
     * @test
     */
    public function can_verify_password()
    {
        $response = $this->post('/api/users/verify-password', [
            'password' => 'password'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }

    // GET REQUESTS

    /**
     * @test
     */
    public function can_show_user()
    {
        $response = $this->get('/api/users/' . $this->user->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user'
            ]
        ]);
    }

    /**
     * @test
     */
    public function policy_blocks_from_showing()
    {
        $user = create('User');

        $response = $this->get('/api/users/' . $user->id);

        $response->assertStatus(403);
        $response->assertJsonStructure([
            'success',
            'message'
        ]);
    }
}
