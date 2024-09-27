<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class QuickBooksControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function can_redirect_to_auth()
    {
        $response = $this->get('/api/quickbooks/authorize');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'auth_url'
            ]
        ]);
    }
}
