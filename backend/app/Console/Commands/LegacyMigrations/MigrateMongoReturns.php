<?php

namespace App\Console\Commands\LegacyMigrations;

use App\CustomClass\LegacyConnection;
use App\Models\Item;
use App\Models\PosOrder;
use App\Models\PosReturn;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;

class MigrateMongoReturns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongo-migration:returns {user : MONGO_ID of the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates Returns data from MongoDB to this DB';

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
        $adminUser = User::where('mongo_id', $legacyUserId)->first();
        $preferences = $adminUser->organization->preferences;
        $auth = Http::post(config('app.url') . '/api/users/authenticate', ['username' => $adminUser->username, 'password' => 'Temp123']);

        $mongoIds = PosReturn::without('posReturnItems')
            ->whereNotNull('mongo_id')
            ->where('organization_id', $adminUser->organization_id)
            ->select('mongo_id')
            ->get()
            ->pluck('mongo_id')
            ->toArray();

        $query = [];
        if ($mongoIds)
        {
            $query = [
                '_id' => 
                [
                    '$nin' => array_map(function ($id) {
                        return new ObjectId($id);
                    }, $mongoIds)
                ]
            ];
        }

        $conn = new LegacyConnection($legacyUserId);
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $returns = $conn->returns()->find($query, $options);

        foreach ($returns as $ret)
        {
            $user = isset($ret['createdBy']) ? User::where('mongo_id', $ret['createdBy'])->first() : $adminUser;
            $returnCreatedAt = $ret['createdAt']->toDateTime()->format('Y-m-d H:i:s');
            $posOrder = PosOrder::where('mongo_id', $ret['orderId'])
                ->where('organization_id', $adminUser->organization_id)
                ->first();

            if (!$posOrder) continue;

            $items = array_map(function($i) use ($ret, $posOrder, $returnCreatedAt) {
                if (isset($i['item']))
                {
                    $orderItems = $posOrder->posOrderItems()->where('item_id', NULL)->get();
                    foreach ($orderItems as $addedItem)
                    {
                        if ($addedItem->addedItem->mongo_id == $i['item']['item']['_id'])
                        {
                            $orderItem = $addedItem;
                            break;
                        }
                    }
                } 
                else 
                {
                    $item = Item::where('mongo_id', $i['_id'])->first();
                    $orderItem = $item->posOrderItems()
                        ->where('pos_order_id', $posOrder->id)
                        ->first();
                }
                
                return [
                    'pos_order_item_id' => $orderItem->id,
                    'item_id' => isset($item->id) ? $item->id : null,
                    'quantity_returned' => $i['quantityReturned'],
                    'action' => $i['action'] == 'backToInventory' ? 1 : 0,
                    'created_at' => $returnCreatedAt
                ];
            }, $ret['items']);

            $returnData = [
                'created_by' => $user->id,
                'organization_id' => $user->organization_id,
                'store_id' => $preferences->stores()->where('mongo_id', $ret['storeId'])->first()->id,
                'pos_order_id' => $posOrder->id,
                'cash' => abs($ret['cash'] * 100),
                'card' => abs($ret['card'] * 100),
                'ebt' => abs($ret['ebt'] * 100),
                'sub_total' => abs($ret['subTotal'] * 100),
                'tax' => abs($ret['tax'] * 100),
                'total' => abs($ret['total'] * 100),
                'tax_rate' => $posOrder['taxRate'],
                'items' => $items,
                'mongo_id' => $ret['_id']->__toString(),
                'created_at' => $returnCreatedAt
            ];

            $req = Http::withToken($auth['data']['user']['token'])
                ->post(config('app.url') . '/api/returns/create', $returnData);
            $data = $req->json();

            if (is_null($data)) 
            {
                Log::channel('single')->debug($req->body());
                Log::channel('single')->debug($returnData);
            }
            
            if ($data['success']) echo("Return created Return ID: " . $ret['_id']->__toString() . "\n");
            else {
                echo("Failed to create Return, Return ID: " . $ret['_id']->__toString() . "\n");
                var_dump($ret);
                var_dump($returnData);
                var_dump($req->body());
                Log::channel('single')->debug($ret);
                Log::channel('single')->debug($returnData);
                Log::channel('single')->debug($req->body());
            }
        }
    }
}
