<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

class PreferenceControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    // POST REQUESTS

    /**
     * @test
     */
    public function can_update_preferences()
    {
        $condition = create('Condition', [
            'preference_id' => $this->preferences->id,
            'name' => 'New'
        ]);

        $response = $this->post('/api/preferences/update/preference', [
            'type' => 'conditions',
            'update' => [
                'id' => $condition->id,
                'name' => 'Used',
            ]
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'preferences'
            ]
        ]);

        $condition->refresh();

        $this->assertEquals('Used', $condition->name);
    }

    /**
     * @test
     */
    public function can_create_store_with_existing_classifications()
    {
        create('Classification', [
            'preference_id' => $this->preferences->id
        ], 3);

        $response = $this->post('/api/preferences/update/preference', [
            'type' => 'stores',
            'update' => [
                'address' => "123 Test St",
                'city' => "Testopia",
                'name' => "Test2",
                'phone' => "632-445-2324",
                'state_id' => State::where("abbreviation", "AZ")->first()->id,
                'tax_rate' => "0.07250",
                'zip' => "85274",
                "receipt_option" => [
                    'footer' => "Thanks For Shopping!\nAll Sales Final.",
                    'name' => "Shoppe Right - Testopia"
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'preferences'
            ]
        ]);

        $this->assertDatabaseCount('stores', 1);
        $this->assertDatabaseCount('preference_options', 6);
    }

    /**
     * @test
     */
    public function can_create_classification_with_multiple_existing_stores()
    {
        $this->createStores(2);

        $response = $this->post('/api/preferences/update/preference', [
            'type' => 'classifications',
            'update' => [
                'name' => "Test 1",
                "discount" => 0,
                "preference_options" => [
                    [
                        "key" => "is_ebt",
                        "value" => false
                    ],
                    [
                        "key" => "is_taxed",
                        "value" => true
                    ]
                ]
            ]
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'preferences'
            ]
        ]);

        $this->assertDatabaseCount('stores', 2);
        $this->assertDatabaseCount('classifications', 1);
        $this->assertDatabaseCount('preference_options', 4);
    }

    /**
     * @test
     */
    public function can_seed_default_classifications()
    {
        $this->createStores(3);
        $response = $this->post('/api/preferences/seed-default', ['default_type' => 'classifications']);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'new_preferences'
            ]
        ]);

        $this->assertDatabaseCount("classifications", 4);
        $this->assertDatabaseCount("preference_options", 24);
    }

    /**
     * @test
     */
    public function can_seed_default_conditions()
    {
        $this->createStores(3);
        $response = $this->post('/api/preferences/seed-default', ['default_type' => 'conditions']);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'new_preferences'
            ]
        ]);

        $this->assertDatabaseCount("conditions", 4);
    }

    /**
     * @test
     */
    public function can_seed_default_discounts()
    {
        $this->createStores(3);
        $response = $this->post('/api/preferences/seed-default', ['default_type' => 'discounts']);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'new_preferences'
            ]
        ]);

        $this->assertDatabaseCount("discounts", 2);
    }

    /**
     * @test
     */
    public function can_create_station()
    {
        $store = $this->createStores();

        $response = $this->post('/api/preferences/update/preference', [
            'type' => 'checkout_stations',
            'update' => [
                'preference_id' => $this->preferences->id,
                'store_id' => $store->id,
                'name' => 'Test Station'
            ]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('checkout_stations', 1);
    }

    /**
     * @test
     */
    public function can_update_station()
    {
        $store = $this->createStores();

        $station = create('CheckoutStation', [
            'preference_id' => $this->preferences->id, 
            'store_id' => $store->id,
            'name' => 'Test Station'
        ]);

        $response = $this->post('/api/preferences/update/preference', [
            'type' => 'checkout_stations', 
            'update' => [
                'id' => $station->id, 
                'name' => 'Station'
            ]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('checkout_stations', 1);
        $this->assertDatabaseMissing('checkout_stations', ['name' => 'Test Station']);
        $this->assertDatabaseHas('checkout_stations', ['name' => 'Station']);
    }

    /**
     * @test
     */
    public function cant_use_duplicate_terminal_on_station()
    {
        $store = $this->createStores();

        create('CheckoutStation', [
            'preference_id' => $this->preferences->id,
            'store_id' => $store->id,
            'name' => 'Test Station',
            'terminal' => 'TEST'
        ]);

        $response = $this->post('/api/preferences/update/preference', [
            'type' => 'checkout_stations',
            'update' => [
                'preference_id' => $this->preferences->id,
                'store_id' => $store->id,
                'name' => 'Test Station 2',
                'terminal' => 'TEST'
            ]
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'You can only assign a terminal to one checkout station.'
        ]);
        $this->assertDatabaseCount('checkout_stations', 1);
    }

    /**
     * @test
     */
    public function can_update_multiple_preferences()
    {
        $store = $this->createStores();

        $station = create('CheckoutStation', [
            'preference_id' => $this->preferences->id,
            'store_id' => $store->id,
            'name' => 'Test Station',
            'terminal' => 'TEST'
        ]);

        $response = $this->post('/api/preferences/update/multiple', [
            'updates' => [
                [
                    'type' => 'checkout_stations',
                    'update' => [
                        'id' => $station->id,
                        'terminal' => null
                    ]
                ],
                [
                    'type' => 'checkout_stations',
                    'update' => [
                        'preference_id' => $this->preferences->id,
                        'store_id' => $store->id,
                        'name' => 'Test Station 2',
                        'terminal' => 'TEST'
                    ]
                ]
            ]
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseCount('checkout_stations', 2);
    }

    /**
     * @test
     */
    public function can_delete_station()
    {
        $store = $this->createStores();

        $station = create('CheckoutStation', [
            'preference_id' => $this->preferences->id, 
            'store_id' => $store->id,
            'name' => 'Test Station'
        ]);

        $response = $this->post('/api/preferences/update/preference', [
            'type' => 'checkout_stations',
            'update' => [
                'id' => $station->id,
                'deleted_at' => true
            ]
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseCount('checkout_stations', 1);
    }

    /**
     * @test
     */
    public function can_save_merchant_preferences()
    {
        $response = $this->post('/api/preferences/update', [
            'using_merchant_partner' => true,
            'merchant_id' => '496160873888'
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('preferences', [
            'using_merchant_partner' => true,
            'merchant_id' => '496160873888'
        ]);
    }

    // GET REQUESTS

    /**
     * @test
     */
    public function can_get_preferences()
    {
        $response = $this->get('/api/preferences');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'preferences'
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_get_merchant_info()
    {
        $response = $this->get('/api/preferences/merchant');

        $response->assertStatus(400);

        $this->preferences->organization->slug = 'shoppe-right';
        $this->preferences->organization->save();
        $response = $this->get('/api/preferences/merchant?slug=shoppe-right');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'stores',
                'classifications',
                'conditions'
            ]
        ]);
    }

    // HELPERS

    private function createStores($count=1)
    {
        return create('Store', [
            'organization_id' => $this->organization_id, 
            'preference_id' => $this->preferences->id
        ], $count);
    }
}
