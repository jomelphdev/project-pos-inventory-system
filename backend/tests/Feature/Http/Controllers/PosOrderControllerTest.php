<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\PosOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\PosOrderController
 */
class PosOrderControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    // POST REQUESTS

    /**
     * @test
     */
    // public function index_behaves_as_expected()
    // {
    //     $posOrders = factory(PosOrder::class, 3)->create();

    //     $response = $this->get(route('pos-order.index'));

    //     $response->assertOk();
    //     $response->assertJsonStructure([]);
    // }

    /**
     * @test
     */
    public function store_uses_form_request_validation()
    {
        $this->assertActionUsesFormRequest(
            \App\Http\Controllers\PosOrderController::class,
            'store',
            \App\Http\Requests\PosOrderStoreRequest::class
        );
    }

    /**
     * @test
     */
    public function store_saves()
    {
        $posOrder = make('PosOrder', ['organization_id' => $this->organization_id]);

        $response = $this
            ->post(
                '/api/orders/create', 
                $this->prepareOrderForRequest($posOrder)
            );
            
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'order'
            ]
        ]);

        $this->assertDatabaseHas('pos_orders', ['id' => $response->json()['data']['order']['id']]);
    }

    /**
     * @test
     */
    public function policy_blocks_from_store()
    {
        $this->user->removeRole("owner");
        $posOrder = make('PosOrder', ['organization_id' => $this->organization_id]);

        $response = $this
            ->post(
                '/api/orders/create',
                $this->prepareOrderForRequest($posOrder)
            );
        
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function can_calculate_order_totals()
    {
        $itemPrices = [
            [
                'price' => 250,
                'quantity' => 3,
            ],
            [
                'price' => 975,
                'quantity' => 1,
            ],
            [
                'price' => 4000,
                'quantity' => 1,
            ],
            [
                'price' => 115,
                'quantity' => 5,
            ]
        ];

        $classification = create('Classification');
        $orderItems = [
            [
                'id' => 'addedItem_1',
                'price' => 750,
                'quantity_ordered' => 2,
                'classification_id' => $classification->id,
                'added_item' => true
            ],
            [
                'id' => 'addedItem_2',
                'price' => 500,
                'quantity_ordered' => 1,
                'classification_id' => $classification->id,
                'added_item' => true
            ],
        ];

        foreach ($itemPrices as $obj)
        {
            $item = create('Item',
                [
                    'organization_id' => $this->organization_id,
                    'price' => $obj['price']
                ]
            );

            array_push(
                $orderItems,
                [
                    'id' => $item->id,
                    'price' => $obj['price'],
                    'quantity_ordered' => $obj['quantity']
                ]
            );
        }
        
        $store = create('Store', ['tax_rate' => .05]);
        $response = $this
            ->post(
                '/api/orders/calculate-totals', 
                [
                    'items' => $orderItems,
                    'store_id' => $store->id,
                    'is_taxed' => true,
                    'ebt_order' => false
                ]
            );
            
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item_totals',
                'sub_total',
                'taxable_sub_total',
                'ebt_sub_total',
                'non_taxed_sub_total',
                'all_non_taxed_sub_total',
                'tax',
                'total',
                'ebt_eligible'
            ]
        ]);
        
        $data = $response->json()['data'];
        $orderItemsCollection = collect($orderItems);
        $subTotal = $orderItemsCollection->sum(function ($item) {
            return $item['price'] * $item['quantity_ordered'];
        });
        $tax = $subTotal * $store->tax_rate;
        
        $this->assertEquals(
            $subTotal + $tax, 
            $data['total']
        );
    }

    /**
     * @test
     */
    public function can_calculate_order_totals_with_item_specific_discounts() 
    {
        $item = create('Item', ['organization_id' => $this->organization_id, 'price' => 1000]);
        $discount = create('ItemSpecificDiscount', ['item_id' => $item->id, 'quantity' => 2, 'discount_amount' => 1500, 'discount_type' => 'amount', 'times_applicable' => null]);
        $store = create('Store', ['tax_rate' => 0]);
        $orderItems = [
            [
                'id' => $item->id,
                'price' => $item->price,
                'quantity_ordered' => 2
            ]
        ];
        
        $response = $this
            ->post(
                '/api/orders/calculate-totals', 
                [
                    'items' => $orderItems,
                    'store_id' => $store->id,
                    'is_taxed' => false,
                    'ebt_order' => false
                ]
            );
            
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'item_totals',
                'sub_total',
                'taxable_sub_total',
                'ebt_sub_total',
                'non_taxed_sub_total',
                'all_non_taxed_sub_total',
                'tax',
                'total',
                'ebt_eligible'
            ]
        ]);

        $data = $response->json()['data'];
        
        $this->assertEquals(
            1500, 
            $data['total']
        );
        $this->assertEquals(
            "Buy 2 for $15",
            $data['item_totals']['totals'][0]['discount_description']
        );

        $orderItems[0]['quantity_ordered'] = 3;
        $response = $this
            ->post(
                '/api/orders/calculate-totals', 
                [
                    'items' => $orderItems,
                    'store_id' => $store->id,
                    'is_taxed' => false,
                    'ebt_order' => false
                ]
            );

        $data = $response->json()['data'];

        $this->assertEquals(
            2500, 
            $data['total']
        );
        $this->assertEquals(
            "Buy 2 for $15",
            $data['item_totals']['totals'][0]['discount_description']
        );

        $orderItems[0]['quantity_ordered'] = 4;
        $response = $this
            ->post(
                '/api/orders/calculate-totals', 
                [
                    'items' => $orderItems,
                    'store_id' => $store->id,
                    'is_taxed' => false,
                    'ebt_order' => false
                ]
            );

        $data = $response->json()['data'];

        $this->assertEquals(
            3000, 
            $data['total']
        );
        $this->assertEquals(
            "Buy 2 for $15 x2",
            $data['item_totals']['totals'][0]['discount_description']
        );

        $discount->expires_at = now()->subDay();
        $discount->save();
        $response = $this
            ->post(
                '/api/orders/calculate-totals', 
                [
                    'items' => $orderItems,
                    'store_id' => $store->id,
                    'is_taxed' => false,
                    'ebt_order' => false
                ]
            );

        $data = $response->json()['data'];

        $this->assertEquals(
            4000, 
            $data['total']
        );
        $this->assertEquals(
            false,
            isset($data['item_totals']['totals'][0]['discount_description'])
        );

        $discount->expires_at = now()->addDays(2);
        $discount->save();
        $response = $this
            ->post(
                '/api/orders/calculate-totals', 
                [
                    'items' => $orderItems,
                    'store_id' => $store->id,
                    'is_taxed' => false,
                    'ebt_order' => false
                ]
            );

        $data = $response->json()['data'];

        $this->assertEquals(
            3000, 
            $data['total']
        );
        $this->assertEquals(
            "Buy 2 for $15 x2",
            $data['item_totals']['totals'][0]['discount_description']
        );

        $discount->active_at = now();
        $discount->save();
        $response = $this
            ->post(
                '/api/orders/calculate-totals', 
                [
                    'items' => $orderItems,
                    'store_id' => $store->id,
                    'is_taxed' => false,
                    'ebt_order' => false
                ]
            );

        $data = $response->json()['data'];

        $this->assertEquals(
            3000, 
            $data['total']
        );
        $this->assertEquals(
            "Buy 2 for $15 x2",
            $data['item_totals']['totals'][0]['discount_description']
        );

        $discount->active_at = now()->addDay();
        $discount->save();
        $response = $this
            ->post(
                '/api/orders/calculate-totals', 
                [
                    'items' => $orderItems,
                    'store_id' => $store->id,
                    'is_taxed' => false,
                    'ebt_order' => false
                ]
            );

        $data = $response->json()['data'];

        $this->assertEquals(
            4000, 
            $data['total']
        );
        $this->assertEquals(
            false,
            isset($data['item_totals']['totals'][0]['discount_description'])
        );
    }

    /**
     * @test
     */
    public function can_calculate_order_payment() 
    {
        $response = $this->post('/api/orders/calculate-payment', [
            'cash' => 1000,
            'card' => 1000,
            'ebt' => 500,
            'total' => 2500
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'success' => true,
            'data' => [
                'amount_remaining' => 0,
                'amount_paid' => 2500,
                'change' => 0
            ]
        ]);

        $response = $this->post('/api/orders/calculate-payment', [
            'card' => 1000,
            'total' => 2500
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'success' => true,
            'data' => [
                'amount_remaining' => 1500,
                'amount_paid' => 1000,
                'change' => 0
            ]
        ]);

        $response = $this->post('/api/orders/calculate-payment', [
            'cash' => 4000,
            'total' => 2500
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'success' => true,
            'data' => [
                'amount_remaining' => 0,
                'amount_paid' => 4000,
                'change' => 1500
            ]
        ]);
    }

    // GET REQUESTS

    /**
     * @test
     */
    public function can_get_orders_for_organization()
    {
        create('PosOrder', ['organization_id' => $this->organization_id], 10);
        $response = $this->get('/api/orders/organization');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'orders'
            ]
        ]);

        $data = $response->json()['data'];

        $this->assertCount(10, $data['orders']);
    }

    /**
     * @test
     */
    public function show_behaves_as_expected()
    {
        $posOrder = create('PosOrder', ['organization_id' => $this->organization_id]);

        $response = $this->get('/api/orders/' . $posOrder->id);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'order'
            ]
        ]);
    }

    /**
     * @test
     */
    public function policy_blocks_from_showing()
    {
        $someoneElsesOrganization = create('Organization');
        $posOrder = create('PosOrder', ['organization_id' => $someoneElsesOrganization->id]);

        $response = $this->get('/api/orders/' . $posOrder->id);
        
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function can_get_order_for_return()
    {
        $posOrder = create('PosOrder', ['organization_id' => $this->organization_id]);

        $response = $this->get('/api/orders/return/' . $posOrder->id);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'order'
            ]
        ]);
    }

    /**
     * @test
     */
    // public function destroy_deletes_and_responds_with()
    // {
    //     $posOrder = PosOrder::factory()->create(['organization_id' => $this->organization_id]);
    //     $orderItem = PosOrderItem::factory()->create(['pos_order_id' => $posOrder->id]);


    //     $response = $this
    //         ->withHeader('Authorization', $this->authToken)
    //         ->post(env('APP_URL') . '/api/orders/delete/' . $posOrder->id);
        
    //     $response->assertOk();
    //     $this->assertDeleted($posOrder);
    //     $this->assertDeleted($orderItem);
    // }

    // HELPERS

    private function prepareOrderForRequest($order)
    {
        $order = $order->toArray();
        $order['cash'] = isset($order['cash']) ? $order['cash'] : null;
        $order['card'] = isset($order['card']) ? $order['card'] : null;
        $order['ebt'] = isset($order['ebt']) ? $order['ebt'] : null;
        $order['sub_total'] = isset($order['sub_total']) ? $order['sub_total'] : null;
        $order['tax'] = isset($order['tax']) ? $order['tax'] : null;
        $order['total'] = isset($order['total']) ? $order['total'] : null;
        $order['amount_paid'] = isset($order['amount_paid']) ? $order['amount_paid'] : null;
        $order['change'] = isset($order['change']) ? $order['change'] : null;
        
        $items = PosOrderItem::factory()
            ->count(3)
            ->make(['pos_order_id' => null])
            ->each(function (PosOrderItem $item) use ($order) {
                $item['id'] = $item->item_id;
                create('Quantity', [
                    'item_id' => $item->item_id,
                    'store_id' => $order['store_id'],
                    'created_by' => $order['created_by'],
                    'quantity_received' => rand($item->quantity_ordered, $item->quantity_ordered + 10),
                    'message' => 'Test Quantity'
                ]);
            });

        $order['items'] = array_map(function ($item) {
            $item['price'] = $item['price'];

            return $item;
        }, $items->toArray());

        return $order;
    }
}
