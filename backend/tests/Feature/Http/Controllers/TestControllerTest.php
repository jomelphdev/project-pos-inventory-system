<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Classification;
use App\Models\Condition;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TestControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function can_seed_test_user()
    {
        $response = $this->post('/api/test/seed-test-user');

        $response->assertStatus(200);
        $this->assertDatabaseHas("users", ["username" => "TestUser"]);
        $this->assertDatabaseCount("classifications", 4);
        $this->assertDatabaseCount("conditions", 4);
        $this->assertDatabaseCount("discounts", 1);
        $this->assertDatabaseCount("stores", 3);
        $this->assertDatabaseCount("receipt_options", 1);
    }

    /**
     * @test
     */
    public function can_clear_test_data()
    {
        Artisan::call("e2e:seed-test-user");
        $user = User::where("username", "TestUser")->first();

        create("AddedItem", [
            "organization_id" => $user->organization_id,
            "classification_id" => Classification::first()->id,
            "created_by" => $user->id
        ], 10);

        create("Item", [
            "organization_id" => $user->organization_id,
            "classification_id" => Classification::first()->id,
            "condition_id" => Condition::first()->id,
            "created_by" => $user->id
        ], 10);

        $response = $this->post('/api/test/clear');
        
        $response->assertStatus(200);
        $this->assertDatabaseHas("users", ["username" => "TestUser"]);
        $this->assertDatabaseCount("added_items", 0);
        $this->assertDatabaseCount("classifications", 4);
        $this->assertDatabaseCount("conditions", 4);
        $this->assertDatabaseCount("discounts", 1);
        $this->assertDatabaseCount("items", 0);
        $this->assertDatabaseCount("manifests", 0);
        $this->assertDatabaseCount("manifest_items", 0);
        $this->assertDatabaseCount("pos_orders", 0);
        $this->assertDatabaseCount("pos_order_items", 0);
        $this->assertDatabaseCount("pos_returns", 0);
        $this->assertDatabaseCount("pos_return_items", 0);
        $this->assertDatabaseCount("quantities", 0);
        $this->assertDatabaseCount("receipt_options", 1);
        $this->assertDatabaseCount("stores", 3);
    }

    /**
     * @test
     */
    public function can_delete_test_user()
    {
        Artisan::call("e2e:seed-test-user");
        $response = $this->post('/api/test/delete-test-user');

        $response->assertStatus(200);
        $this->assertDatabaseMissing("users", ["username" => "TestUser"]);
    }

    // TESTS FOR /test/seed-spec-data

    /**
     * @test
     */
    public function can_seed_user_create_spec_data()
    {
        $user = make("User", [
            "first_name" => "Sam",
            "last_name" => "Walton",
            "username" => "swalton",
            "email" => "swalton@test.com",
            "password" => "Test1234"
        ]);

        UserService::createUser($user);

        $response = $this->post('/api/test/seed-spec-data', ["spec_name" => "user_create"]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            "success",
            "data"
        ]);
        $this->assertDatabaseMissing("users", ["email" => "swalton@test.com"]);
    }

    /**
     * @test
     */
    public function can_seed_preference_spec_data()
    {
        Artisan::call("e2e:seed-test-user");
        $user = User::where("username", "TestUser")->first();

        $classification = create("Classification", ["preference_id" => $user->preferences->id]);
        create("Item", ["organization_id" => $user->organization_id, "classification_id" => $classification->id]);
        $store = create("Store", ["preference_id" => $user->preferences->id]);
        $classification->preferenceOptions()->createMany([
            [
                "store_id" => $store->id,
                "key" => "is_ebt",
                "value" => false
            ],
            [
                "store_id" => $store->id,
                "key" => "is_taxed",
                "value" => true
            ],
        ]);

        $response = $this->post('/api/test/seed-spec-data', ["spec_name" => "preferences"]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            "success",
            "data"
        ]);

        
        $preferenceId = $user->preferences->id;
        $this->assertDatabaseMissing("classifications", ["preference_id" => $preferenceId]);
        $this->assertDatabaseMissing("conditions", ["preference_id" => $preferenceId]);
        $this->assertDatabaseMissing("discounts", ["preference_id" => $preferenceId]);
        $this->assertDatabaseMissing("stores", ["preference_id" => $preferenceId]);
        $this->assertDatabaseMissing("preference_options", ["model_id" => $classification->id, "store_id" => $store->id]);
    }

    /**
     * @test
     */
    public function can_seed_item_edit_spec_data()
    {
        Artisan::call("e2e:seed-test-user");
        $response = $this->post('/api/test/seed-spec-data', ["spec_name" => "item_edit"]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            "success",
            "data" => [
                "item"
            ]
        ]);
        $this->assertDatabaseCount("items", 1);
        $this->assertDatabaseCount("quantities", 1);
    }

    /**
     * @test
     */
    public function can_seed_pos_create_order_spec_data()
    {
        Artisan::call("e2e:seed-test-user");
        $response = $this->post('/api/test/seed-spec-data', ["spec_name" => "pos_create_order"]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            "success",
            "data" => [
                "skus"
            ]
        ]);
        $this->assertDatabaseCount("items", 4);
        $this->assertDatabaseCount("quantities", 4);
    }

    /**
     * @test
     */
    public function can_seed_pos_create_return_spec_data()
    {
        Artisan::call("e2e:seed-test-user");
        $response = $this->post('/api/test/seed-spec-data', ["spec_name" => "pos_create_return"]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            "success",
            "data" => [
                "items",
                "order",
                "totals"
            ]
        ]);
        $this->assertDatabaseCount("items", 2);
        $this->assertDatabaseCount("quantities", 2);
        $this->assertDatabaseCount("added_items", 1);
        $this->assertDatabaseCount("pos_orders", 1);
    }

    // END OF /test/seed-spec-data
}
