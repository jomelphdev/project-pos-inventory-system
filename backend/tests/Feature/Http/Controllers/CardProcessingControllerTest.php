<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CardProcessingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->preferences->merchant_id = config('services.cardconnect.mid');
        $this->preferences->save();
    }

    /**
     * @test
     */
    public function can_verify_merchant()
    {
        $response = $this->get('/api/card/verify?merchant_id=' . config('services.cardconnect.mid') . '&merchant_username=' . config('services.cardconnect.username') . '&merchant_password=' . config('services.cardconnect.password'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }

    /**
     * @test
     */
    public function cant_verify_merchant()
    {
        $response = $this->get('/api/card/verify?merchant_id=123456&merchant_username=' . config('services.cardconnect.username') . '&merchant_password=' . config('services.cardconnect.password'));

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'success',
            'message'
        ]);
    }

    /**
     * @test
     */
    public function can_get_terminals()
    {
        $response = $this->get('/api/card/terminals?merchant_id=' . config('services.cardconnect.mid'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'terminals'
            ]
        ]);
    }
}
