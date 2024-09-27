<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function can_get_daily_sales_report()
    {
        $store = create('Store', [
            'preference_id' => $this->preferences->id,
            'organization_id' => $this->organization_id
        ]);

        create('PosOrder', [
            'organization_id' => $this->organization_id,
            'created_by' => $this->user->id,
            'store_id' => $store->id
        ], 10);

        $response = $this->post('/api/reports/daily-sales', [
            'store_id' => $store->id,
            'date' => now()->timestamp
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message'
        ]);

        sleep(5);

        $response = $this->post('/api/reports/daily-sales', [
            'store_id' => $store->id,
            'date' => now()->timestamp
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    
        $headers = $response->headers->all();
        $disposition = $headers['content-disposition'][0];

        $filename = substr(
            $disposition, 
            strpos($disposition, 'filename')
        );
        $filename = explode('=', $filename)[1];
        $filePath = 'reports/' . $this->organization_id . '/daily_sales/' . $store->id . '/' . $filename;

        $this->assertTrue(Storage::disk('s3')->exists($filePath));
        Storage::disk('s3')->delete($filePath);
    }

    /**
     * @test
     */
    public function can_get_daily_sales_report_data()
    {
        $store = create('Store', [
            'preference_id' => $this->preferences->id,
            'organization_id' => $this->organization_id
        ]);

        create('PosOrder', [
            'organization_id' => $this->organization_id,
            'created_by' => $this->user->id,
            'store_id' => $store->id
        ], 10);

        $response = $this->post('/api/reports/data/daily-sales', [
            'store_id' => $store->id,
            'date' => now()->timestamp
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'orders',
                'order_totals',
                'returns',
                'return_totals',
                'totals'
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_get_sales_report()
    {
        $monthAgo = now()->subDays(30);
        $stores = create('Store', [
            'preference_id' => $this->preferences->id,
            'organization_id' => $this->organization_id
        ], 3)
            ->each(function ($store) use ($monthAgo) {
                create('PosOrder', [
                    'organization_id' => $this->organization_id,
                    'created_by' => $this->user->id,
                    'store_id' => $store->id
                ], 10);
        
                create('PosOrder', [
                    'organization_id' => $this->organization_id,
                    'created_by' => $this->user->id,
                    'store_id' => $store->id,
                    'created_at' => $monthAgo->toDateTime()
                ], 10);
            });

        $storeIds = collect($stores)->pluck('id');
        
        $response = $this->post('/api/reports/sales', [
            'stores' => $storeIds,
            'start_date' => $monthAgo->timestamp,
            'end_date' => now()->timestamp
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message'
        ]);

        sleep(5);

        $response = $this->post('/api/reports/sales', [
            'stores' => $storeIds,
            'start_date' => $monthAgo->timestamp,
            'end_date' => now()->timestamp
        ]);
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        foreach ($storeIds as $id) 
        {
            $filePath = 'reports/' . $this->organization_id . '/sales/' . $id;

            $this->assertTrue(Storage::disk('s3')->exists($filePath));
            Storage::disk('s3')->deleteDirectory($filePath);
        }
    }

    /**
     * @test
     */
    public function can_get_sales_report_data()
    {
        $monthAgo = now()->subDays(30);
        $stores = create('Store', [
            'preference_id' => $this->preferences->id,
            'organization_id' => $this->organization_id
        ], 3)
            ->each(function ($store) use ($monthAgo) {
                create('PosOrder', [
                    'organization_id' => $this->organization_id,
                    'created_by' => $this->user->id,
                    'store_id' => $store->id
                ], 10);
        
                create('PosOrder', [
                    'organization_id' => $this->organization_id,
                    'created_by' => $this->user->id,
                    'store_id' => $store->id,
                    'created_at' => $monthAgo->toDateTime()
                ], 10);
            });

        $storeIds = collect($stores)->pluck('id');

        $response = $this->post('/api/reports/data/sales', [
            'stores' => $storeIds,
            'start_date' => $monthAgo->timestamp,
            'end_date' => now()->timestamp
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'sales',
                'totals'
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_get_inventory_report()
    {
        $stores = create('Store', [
            'preference_id' => $this->preferences->id,
            'organization_id' => $this->organization_id
        ], 3)
            ->each(function ($store) {
                create('Item', [
                    'organization_id' => $this->organization_id,
                    'created_by' => $this->user->id,
                ], 10)
                    ->each(function ($item) use ($store) {
                        create('Quantity', [
                            'item_id' => $item->id,
                            'store_id' => $store->id,
                            'quantity_received' => rand(1, 10),
                            'message' => '[TEST] Quantity Created'
                        ]);
                    });
            });

        $storeIds = collect($stores)->pluck('id');
        
        $response = $this->post('/api/reports/inventory', [
            'stores' => $storeIds
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message'
        ]);

        sleep(5);

        $response = $this->post('/api/reports/inventory', [
            'stores' => $storeIds
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * @test
     */
    public function can_create_consignment_invoice()
    {
        $consignor = create('Consignor', ['preference_id' => $this->preferences->id]);
        $item = create('Item', ['organization_id' => $this->organization_id, 'price' => 2500, 'consignor_id' => $consignor->id, 'consignment_fee' => 500]);
        create('Quantity', ['item_id' => $item->id, 'quantity_received' => 2]);
        $order = create('PosOrder', ['organization_id' => $this->organization_id, 'created_by' => $this->user->id]);
        create('PosOrderItem', ['pos_order_id' => $order->id, 'item_id' => $item->id, 'price' => $item->price, 'consignment_fee' => $item->consignment_fee, 'quantity_ordered' => 2]);
        $return = create('PosReturn', ['organization_id' => $this->organization_id, 'created_by' => $this->user->id, 'pos_order_id' => $order->id]);
        create('PosReturnItem', ['pos_return_id' => $return->id, 'pos_order_item_id' => $order->posOrderItems()->first()->id, 'item_id' => $item->id, 'quantity_returned' => 1, 'consignment_fee' => $item->consignment_fee]);


        $response = $this->post('/api/reports/consignment-invoice', ['consignor_id' => $consignor->id]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'invoice'
            ]
        ]);
        
        $invoice = $response->json()['data']['invoice'];

        $this->assertEquals(2000, $invoice['amount_paid']);
        $this->assertEquals(500, $invoice['amount_collected']);
    }

    /**
     * @test
     */
    public function can_get_consignment_invoices()
    {
        create('ConsignorInvoice', ['organization_id' => $this->organization_id], 3);

        $response = $this->get('/api/reports/consignment-invoices');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'current_page',
                'invoices',
                'to',
                'total'
            ]
        ]);
        
        $invoices = $response->json()['data']['invoices'];

        $this->assertEquals(3, count($invoices));
    }

    /**
     * @test
     */
    public function can_get_consignment_report_data()
    {
        $consignor = create('Consignor', ['preference_id' => $this->preferences->id]);
        $item = create('Item', ['organization_id' => $this->organization_id, 'price' => 2500, 'consignor_id' => $consignor->id, 'consignment_fee' => 500]);
        create('Quantity', ['item_id' => $item->id, 'quantity_received' => 2]);
        $order = create('PosOrder', ['organization_id' => $this->organization_id, 'created_by' => $this->user->id]);
        create('PosOrderItem', ['pos_order_id' => $order->id, 'item_id' => $item->id, 'price' => $item->price, 'consignment_fee' => $item->consignment_fee, 'quantity_ordered' => 2]);

        $response = $this->get('/api/reports/data/consignment');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);

        $body = $response->json()['data'];

        $this->assertEquals(1, count($body));

        $data = $body[0];

        $this->assertEquals($consignor->id, $data['consignor_id']);
        $this->assertEquals(1000, $data['consignment_sum']);
        $this->assertEquals(4000, $data['amount_owed']);
        $this->assertEquals(2, $data['sales']);

        create('ConsignorInvoice', ['organization_id' => $this->organization_id, 'consignor_id' => $consignor->id]);
        
        $response = $this->get('/api/reports/data/consignment');

        $this->assertEquals(0, count($response->json()['data']));
    }

    /**
     * @test
     */
    public function can_get_drawers_report_data()
    {
        $store = create('Store', ['preference_id' => $this->preferences->id, 'organization_id' => $this->organization_id]);
        $station = create('CheckoutStation', ['preference_id' => $this->preferences->id, 'store_id' => $store->id, 'drawer_balance' => 10000, 'last_balanced' => now()->toDateString()]);
        $order = create('PosOrder', ['organization_id' => $this->organization_id, 'store_id' => $store->id, 'checkout_station_id' => $station->id, 'cash' => 1000, 'card' => 100, 'tax' => 0, 'total' => 1100, 'change' => 0, 'created_by' => $this->user->id]);

        $response = $this->get('/api/reports/data/drawers');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);

        $body = $response->json()['data'];

        $this->assertEquals(1, count($body));

        $data = $body[0];
        $employeeData = $data['employee_data'];
        $employee = $employeeData[0];

        $this->assertEquals(1, count($employeeData));
        $this->assertEquals($station->drawer_balance, $data['starting_balance']);
        $this->assertEquals($station->drawer_balance + $order->cash, $data['current_balance']);
        $this->assertEquals($order->cash, $data['difference']);
        $this->assertEquals($this->user->id, $employee['user_id']);
        $this->assertEquals($order->cash, $employee['cash_transacted']);
        $this->assertEquals($order->total, $employee['total_transacted']);
        $this->assertEquals(1, $employee['orders']);

        $return = create('PosReturn', ['organization_id' => $this->organization_id, 'store_id' => $store->id, 'checkout_station_id' => $station->id, 'created_by' => $this->user->id, 'cash' => 500, 'card' => 100, 'total' => 600]);
        
        $response = $this->get('/api/reports/data/drawers');

        $body = $response->json()['data'];
        $data = $body[0];
        $employeeData = $data['employee_data'];
        $employee = $employeeData[0];

        $this->assertEquals(1, count($employeeData));
        $this->assertEquals($station->drawer_balance, $data['starting_balance']);
        $this->assertEquals($station->drawer_balance + $order->cash - $return->cash, $data['current_balance']);
        $this->assertEquals($order->cash - $return->cash, $data['difference']);
        $this->assertEquals($this->user->id, $employee['user_id']);
        $this->assertEquals($order->cash - $return->cash, $employee['cash_transacted']);
        $this->assertEquals($order->total - $return->total, $employee['total_transacted']);
        $this->assertEquals(1, $employee['orders']);
        $this->assertEquals(1, $employee['returns']);
    }

    /**
     * @test
     */
    public function can_set_new_drawer_balance()
    {
        $store = create('Store', ['preference_id' => $this->preferences->id, 'organization_id' => $this->organization_id]);
        $station = create('CheckoutStation', ['preference_id' => $this->preferences->id, 'store_id' => $store->id, 'drawer_balance' => 10000, 'last_balanced' => now()->toDateString()]);
        $order = create('PosOrder', ['organization_id' => $this->organization_id, 'store_id' => $store->id, 'checkout_station_id' => $station->id, 'cash' => 1000, 'card' => 100, 'tax' => 0, 'total' => 1100, 'change' => 0, 'created_by' => $this->user->id]);

        $newBal = 15000;
        $response = $this->post('/api/reports/drawer-balance', [
            'checkout_station_id' => $station->id,
            'actual_difference' => 1000,
            'new_balance' => $newBal
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
        $this->assertDatabaseCount('drawer_logs', 1);
        $this->assertDatabaseHas('checkout_stations', ['drawer_balance' => $newBal]);
    }
}
