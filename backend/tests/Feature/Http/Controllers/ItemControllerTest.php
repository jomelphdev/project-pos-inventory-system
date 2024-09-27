<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\ItemController
 */
class ItemControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    // POST REQUESTS

    /**
     * @test
     */
    public function store_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ItemController::class,
            'store',
            \App\Http\Requests\ItemStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves()
    {
        $item = $this->makeItem($this->organization_id);
        
        $response = $this->post(
            '/api/items/create', 
            $this->prepareItemForRequest($item)
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item'
            ]
        ]);
        
        $data = $response->json()['data'];

        $this->assertDatabaseHas('items', ['id' => $data['item']['id']]);
    }

    /**
     * @test
     */
    public function store_doesnt_save()
    {
        $item = make('Item');

        $response = $this
            ->post(
                '/api/items/create', 
                $this->prepareItemForRequest($item)
            );
            
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'messages'
        ]);
        
        $this->assertDatabaseCount('items', 0);
    }

    /**
     * @test
     */
    public function store_saves_without_classification()
    {
        $item = self::createQuantity(create('Item', [
            'organization_id' => $this->organization_id,
            'classification_id' => null
        ]));

        $response = $this->post(
            '/api/items/create', 
            $this->prepareItemForRequest($item)
        );
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item'
            ]
        ]);
        
        $data = $response->json()['data'];

        $this->assertDatabaseHas('items', ['id' => $data['item']['id']]);
    }

    /**
     * @test
     */
    public function store_saves_without_condition()
    {
        $item = self::createQuantity(create('Item', [
            'organization_id' => $this->organization_id,
            'condition_id' => null
        ]));

        $response = $this->post(
            '/api/items/create', 
            $this->prepareItemForRequest($item)
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item'
            ]
        ]);
        
        $data = $response->json()['data'];

        $this->assertDatabaseHas('items', ['id' => $data['item']['id']]);
    }

    /**
     * @test
     */
    public function store_saves_without_classification_and_condition()
    {
        $item = self::createQuantity(create('Item', [
            'organization_id' => $this->organization_id,
            'classification_id' => null,
            'condition_id' => null
        ]));
        
        $response = $this->post(
            '/api/items/create', 
            $this->prepareItemForRequest($item)
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item'
            ]
        ]);
        
        $data = $response->json()['data'];

        $this->assertDatabaseHas('items', ['id' => $data['item']['id']]);
    }

    /**
     * @test
     */
    public function policy_blocks_from_store()
    {
        $this->user->removeRole("owner");
        $item = $this->makeItem($this->organization_id);

        $response = $this
            ->post(
                '/api/items/create',
                $this->prepareItemForRequest($item)
            );

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function update_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\ItemController::class,
            'update',
            \App\Http\Requests\ItemUpdateRequest::class
        );
    }

    /**
     * @test
     */
    public function update_behaves_as_expected()
    {
        $item = create('Item', ['organization_id' => $this->organization_id]);
        
        $classification = create('Classification',
            [
                'preference_id' => $this->preferences->id,
                'discount' => 10
            ]
        );
        $condition = create('Condition',
            [
                'preference_id' =>  $this->preferences->id,
                'discount' => 10
            ]
        );
        $title = $this->faker->sentence(4);
        $price = $this->faker->numberBetween(1, 10000);
        
        $response = $this
            ->post(
                '/api/items/update/' . $item->id, 
                [
                    'classification_id' => $classification->id,
                    'condition_id' => $condition->id,
                    'title' => $title,
                    'price' => $price
                ]
            );

        $item->refresh();
        
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item'
            ]
        ]);

        $this->assertEquals($classification->id, $item->classification_id);
        $this->assertEquals($condition->id, $item->condition_id);
        $this->assertEquals($title, $item->title);
        $this->assertEquals($price, $item->price);
    }

    /**
     * @test
     */
    public function can_update_item_quantity()
    {
        $item = create('Item', ['organization_id' => $this->organization_id]);
        $quantity = create('Quantity',
            [
                'item_id' => $item->id,
                'quantity_received' => 5,
                'message' => 'Quantity created'
            ]
        );
        $quantityEdit = [
            'quantities' => [
                [
                    'item_id' => $quantity->id,
                    'store_id' => $quantity->store_id,
                    'created_by' => $this->user->id,
                    'quantity_received' => 10,
                    'message' => 'Found extras'
                ]
            ]
        ];


        $response = $this
            ->post(
                '/api/items/update/' . $item->id, 
                $quantityEdit
            );
        
        $item->refresh();
        
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item'
            ]
        ]);
        
        $quantityEntriesTotal = $quantityEdit['quantities'][0]['quantity_received'] + $quantity->quantity_received;
        $this->assertEquals(
            $quantityEntriesTotal, 
            $item->store_quantities[0]['quantity'] // Stores quantity from DB
        );
    }

    /**
     * @test
     */
    public function can_delete_item()
    {
        $item = create('Item', ['organization_id' => $this->organization_id]);

        $response = $this->post('/api/items/delete/' . $item->id);

        $item->refresh();

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'message'
            ]
        ]);

        $this->assertTrue(!is_null($item->deleted_at));
    }

    /**
     * @test
     */
    public function can_upload_inventory()
    {
        $file = new UploadedFile(
            base_path('resources/files/Test_Inventory_Upload.xlsx'),
            'Test_Inventory.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $storeNames = ['Shoppe Right - Unit Falls', 'Shoppe Right - Testopia'];

        foreach ($storeNames as $name)
        {
            create('Store', ['organization_id' => $this->organization_id, 'preference_id' => $this->preferences->id, 'name' => $name]);
        }

        create('Classification', ['preference_id' => $this->preferences->id, 'name' => 'Appliances']);
        create('Condition', ['preference_id' => $this->preferences->id, 'name' => 'Like New']);

        $response = $this->post('/api/items/import', [
            'inventory_file' => $file
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'message'
            ]
        ]);

        sleep(10);

        $this->assertDatabaseCount('items', 9);
        $this->assertDatabaseCount('quantities', 18);
        $this->assertDatabaseCount('item_images', 2);
    }

    /**
     * @test
     */
    public function calculate_item_price()
    {
        $classification = create('Classification', ['discount' => 5]);
        $condition = create('Condition', ['discount' => 5]);
        $item = create('Item', [
            'organization_id' => $this->organization_id,
            'classification_id' => $classification->id,
            'condition_id' => $condition->id,
            'price' => 1000
        ]);
        
        $response = $this
            ->post('/api/items/calculate-price', [
                'price' => $item->price,
                'classification_id' => $item->classification_id,
                'condition_id' => $item->condition_id
            ]);
            
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'price'
            ]
        ]);

        $data = $response->json()['data'];

        $this->assertEquals(900, $data['price']);
    }

    /**
     * @test
     */
    public function calculate_multiple_items_price()
    {
        $prices = [1000, 2599, 4500];
        $resultingPrices = [900, 2339, 4050];
        $items = [];
        foreach ($prices as $price)
        {
            array_push($items, create('Item', ['price' => $price]));
        }

        $discount = create('Discount', ['discount' => 10]);
        $items = array_map(function ($item) use ($discount) {
            return [
                'id' => $item->id,
                'price' => $item->price,
                'discount_id' => $discount->id
            ];
        }, $items);
        
        $response = $this->post('/api/items/list/calculate-price', ['items' => $items]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item_prices'
            ]
        ]);

        $data = $response->json()['data'];

        foreach ($data['item_prices'] as $index => $priceObj) {
            $this->assertEquals($resultingPrices[$index], $priceObj['price']);
        }
    }

    // GET REQUESTS

    /**
     * @test
     */
    public function can_calculate_consignment_fee()
    {
       $consignor = create('Consignor', ['preference_id' => $this->preferences->id, 'consignment_fee_percentage' => 10]);

        $response = $this->get('/api/items/calculate-consignment-fee?consignor_id=' . $consignor->id . '&price=1000');
        
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'consignment_fee',
                'consignment_fee_percentage'
            ]
        ]);

        $fee = $response->json()['data']['consignment_fee'];

        $this->assertEquals(100, $fee);
    }

    /**
     * @test
     */
    public function show_behaves_as_expected()
    {
        $item = create('Item', ['organization_id' => $this->organization_id]);

        $response = $this->get('/api/items/' . $item->id);
        
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item'
            ]
        ]);
    }

    /**
     * @test
     */
    public function policy_blocks_from_showing()
    {
        $someoneElsesOrganization = create('Organization');
        $item = create('Item', ['organization_id' => $someoneElsesOrganization->id]);

        $response = $this->get('/api/items/' . $item->id);
        
        $response->assertStatus(403);
    }

    // ITEM QUERIES
    // POST REQUESTS

    /**
     * @test
     */
    public function can_query_items()
    {
        $items = create('Item', ['organization_id' => $this->organization_id], 10);

        $response = $this->post('/api/items/query', ['query' => ""]);

        $this->assertQueryCount($response, 10);

        $response = $this->post('/api/items/query', ['query' => $items[0]->title]);

        $this->assertQueryCount($response, 1);
    }

    /**
     * @test
     */
    public function can_get_count_of_items_from_query()
    {
        create('Item', ['organization_id' => $this->organization_id], 10);

        $response = $this->post('/api/items/query/count', ['query' => ""]);

        $response->assertJsonStructure([
            'success',
            'data' => [
                'items_count'
            ]
        ]);

        $body = $response->json();

        $this->assertEquals(10, $body['data']['items_count']);
    }

    /**
     * @test
     */
    public function can_query_items_by_upc()
    {
        $upc = "045242472628";
        create('Item', [
            'organization_id' => $this->organization_id,
            'upc' => $upc
        ]);

        $response = $this->post('/api/items/query/upc', ['upc' => $upc]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'items'
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_query_items_by_sku()
    {
        $item = create('Item', ['organization_id' => $this->organization_id]);
        $response = $this->post('/api/items/query/sku', ['sku' => $item->sku]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item'
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_query_upc_data()
    {
        $response = $this->post('/api/items/query/upc-data', ['upc' => "045242472628"]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'upc_item',
                'listed_upc_items',
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_query_upc_and_get_listed_upc_items()
    {
        $upc = "045242472628";
        create('Item', [
            'organization_id' => $this->organization_id,
            'upc' => $upc
        ]);

        $response = $this->post('/api/items/query/upc-data', ['upc' => $upc]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'upc_item',
                'listed_upc_items',
            ]
        ]);

        $body = $response->json();

        $this->assertCount(1, $body['data']['listed_upc_items']);
    }

    // GET

    /**
     * @test
     */
    public function can_get_showcase_items()
    {
        $this->preferences->organization->slug = 'shoppe-right';
        $this->preferences->organization->save();

        create('Item', ['organization_id' => $this->organization_id], 10);
        $response = $this->get('/api/items/query/showcase?slug=shoppe-right');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'current_page',
                'items',
                'to',
                'total'
            ]
        ]);
        
        $body = $response->json();

        $this->assertCount(10, $body['data']['items']);
    }
    
    /**
     * @test
     */
    public function can_get_used_conditions_from_title()
    {
        create('Item', ['organization_id' => $this->organization_id, 'title' => 'TEST ITEM'], 2);
        $response = $this->get('/api/items/query/title-conditions?title=TEST+ITEM');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'used_conditions'
            ]
        ]);
        
        $body = $response->json();

        $this->assertCount(2, $body['data']['used_conditions']);
    }

    // HELPER FUNCTIONS

    private function prepareItemForRequest($item)
    {
        $item = $item->toArray();
        $item['price'] = $item['price'];
        $item['original_price'] = $item['original_price'];
        $item['cost'] = isset($item['cost']) ? $item['cost'] : null;

        return $item;
    }

    private function makeItem($orgId) 
    {
        $item = self::createQuantity(make('Item', [
            'organization_id' => $orgId,
        ]));

        return $item;
    }

    private function createQuantity($item)
    {
        $item['quantities'] = [
            make('Quantity', [
                'quantity_received' => 1,
                'message' => 'Quantity created',
                'created_by' => $item->created_by
            ])
            ->toArray()
        ];

        return $item;
    }

    private function assertQueryCount(TestResponse $response, $expected=0)
    {
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'items'
            ]
        ]);

        $data = $response->json()['data'];

        $this->assertCount($expected, $data['items']);
    }
}
