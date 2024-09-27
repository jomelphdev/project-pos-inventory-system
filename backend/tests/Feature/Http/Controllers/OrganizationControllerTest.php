<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrganizationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function can_save_site_slug()
    {
        $response = $this->post('/api/organization/save-slug', ['slug' => 'shoppe-right']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('organizations', ['slug' => 'shoppe-right']);
    }

    /**
     * @test
     */
    public function cant_save_duplicate_site_slug()
    {
        $this->post('/api/organization/save-slug', ['slug' => 'shoppe-right']);
        
        $org = create('Organization');
        $user = create('User', ['organization_id' => $org->id]);
        $this->actingAs($user);

        $response = $this->post('/api/organization/save-slug', ['slug' => 'shoppe-right']);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Someone else is already using this as their URL please choose a new one and try again.'
        ]);
    }
}
