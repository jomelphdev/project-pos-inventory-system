<?php

namespace App\Console\Commands\LegacyMigrations;

use App\CustomClass\LegacyConnection;
use App\Models\Item;
use App\Models\PosOrder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Money\Money;

class MigrateMongoOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongo-migration:orders {user : MONGO_ID of the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates Orders data from MongoDB to this DB';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $legacyUserId = $this->argument('user');
        $conn = new LegacyConnection($legacyUserId);

        $adminUser = User::where('mongo_id', $legacyUserId)->first();
        $preferences = $adminUser->organization->preferences;
        $auth = Http::post(config('app.url') . '/api/users/authenticate', ['username' => $adminUser->username, 'password' => 'Temp123']);

        $mongoIds = PosOrder::without('posOrderItems')
            ->whereNotNull('mongo_id')
            ->where('organization_id', $adminUser->organization_id)
            ->select('mongo_id')
            ->get()
            ->pluck('mongo_id')
            ->toArray();
        
        $query = [];
        if ($mongoIds)
        {
            $query = ['_id' => ['$nin' => array_map('intval', $mongoIds)]];
        }

        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $orders = $conn->orders()->find($query, $options);
        
        foreach ($orders as $order)
        {
            // sleep(1);

            $user = isset($order['createdBy']) ? User::where('mongo_id', $order['createdBy'])->first() : $adminUser;
            $orderCreatedAt = $order['createdAt']->toDateTime()->format('Y-m-d H:i:s');

            $items = array_filter(
                array_map(function($i) use ($preferences, $orderCreatedAt) {
                    $discount = isset($i['discountId']) ?  $preferences->discounts()->where('mongo_id', $i['discountId'])->first() : null;
                    $discountId = is_null($discount) ? null : $discount->id;
                    $discountAmount = is_null($discount) ? null : $discount->discount;
                    $isEbt = isset($i['isEbt']) ? $i['isEbt'] : null;

                    if (isset($i['item']))
                    {
                        $price = $i['item']['price'] * 100;
                        $classification = isset($i['item']['classificationId']) ? $preferences->classifications()->where('mongo_id', $i['item']['classificationId'])->first() : null;
                        return [
                            'added_item' => true,
                            'discount_id' => $discountId,
                            'discount' => $discountAmount,
                            'price' => $price,
                            'temp_price' => !is_null($discount) && isset($i['item']['orgPrice']) ? $i['item']['orgPrice'] : $price,
                            'quantity_ordered' => $i['quantityOrdered'],
                            'is_ebt' => $isEbt,
                            'classification_id' => $classification ? 
                                $classification->id
                                : $preferences->classifications()->first()->id,
                            'title' => $i['item']['title'],
                            'mongo_id' => $i['item']['_id'],
                            'created_at' => $orderCreatedAt
                        ];
                    }

                    $item = Item::where('mongo_id', $i['_id'])->first();
                    if ($item)
                    {
                        $price = $i['price'] * 100;
                        return [
                            'id' => $item->id,
                            'discount_id' => $discountId,
                            'discount' => $discountAmount,
                            'price' => $price,
                            'original_price' => is_null($discount) || $discountAmount == 1 ? $price : Money::USD($price)->divide(1-$discountAmount)->getAmount(),
                            'quantity_ordered' => $i['quantityOrdered'],
                            'is_ebt' => $isEbt,
                            'created_at' => $orderCreatedAt
                        ];
                    }
                    
                    return null;
                }, $order['items'])
            );

            if (count($items) == 0) 
            {
                echo("Order had no items: " . $order['_id'] . "\n");
                Log::channel('single')->debug($order);
                continue;
            }

            $orderData = [
                'created_by' =>  $user->id,
                'organization_id' => $user->organization_id,
                'store_id' => $preferences->stores()->where('mongo_id', $order['storeId'])->first()->id,
                'cash' => $order['cash'] * 100,
                'card' => $order['card'] * 100,
                'ebt' => $order['ebt'] * 100,
                'sub_total' => $order['subTotal'] * 100,
                'tax' => $order['tax'] * 100,
                'total' => $order['total'] * 100,
                'amount_paid' => $order['amountPaid'] * 100,
                'change' => $order['change'] * 100,
                'tax_rate' => $order['taxRate'],
                'items' => $items,
                'mongo_id' => strval($order['_id']),
                'created_at' => $orderCreatedAt,
            ];

            $req = Http::withToken($auth['data']['user']['token'])
                ->post(config('app.url') . '/api/orders/create', $orderData);
            $data = $req->json();

            if ($data['success']) echo("Order created Order ID: " . $order['_id'] . "\n");
            else {
                echo("Failed to create Order, Order ID: " . $order['_id'] . "\n");
                var_dump($order);
                var_dump($orderData);
                var_dump($req->body());
                Log::channel('single')->debug($order);
                Log::channel('single')->debug($orderData);
                Log::channel('single')->debug($req->body());
            }
        }
    }
}
