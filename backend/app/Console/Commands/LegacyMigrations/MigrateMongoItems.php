<?php

namespace App\Console\Commands\LegacyMigrations;

use App\CustomClass\LegacyConnection;
use App\Models\Item;
use App\Models\Store;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MongoDB\BSON\ObjectId;

class MigrateMongoItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongo-migration:items {user : MONGO_ID of the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates Items data from MongoDB to this DB';

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
        $auth = Http::post(config('app.url') . '/api/users/authenticate', ['username' => $adminUser->username, 'password' => 'Temp123']);

        $preferences = $adminUser->organization()
            ->first()
            ->preferences;

        $mongoIds = Item::without('itemImages')
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

        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $items = $conn->items()->find($query, $options);
        
        foreach ($items as $item) 
        {
            // sleep(1);
            $createdBy = User::where('mongo_id', $item['userId'])->first();
            $itemClassification = $preferences->classifications()->where('mongo_id', $item['classificationId'])->select('id', 'mongo_id')->first();
            $itemCondition = $preferences->conditions()->where('mongo_id', $item['conditionId'])->select('id', 'mongo_id')->first();
            $quantities = array_map(function ($q) use ($createdBy, $item) {
                try
                {
                    $store = Store::where('mongo_id', $q['storeId'])->first();
                }
                catch (Exception $e)
                {
                    return null;
                }

                if (!$store) return null;
                if ($q['quantityRecieved'] < 0 || $q['quantityRecieved'] > 32767 || is_null($q['quantityRecieved'])) $q['quantityRecieved'] = 0;

                return [
                    'store_id' => $store->id,
                    'created_by' => $createdBy->id,
                    'quantity_received' => $q['quantityRecieved'],
                    'message' => 'Database migration.',
                    'created_at' => $item['createdAt']->toDateTime()->format('Y-m-d H:i:s')
                ];
            }, $item['quantities']);
            $itemPrice = $item['price'] * 100;
            $itemImages = array_filter($item['images'], function ($img) {
                if (strpos($img, 'harvardhoodie')) return true;
                return false;
            });

            foreach ($itemImages as &$img) 
            {
                $fileReq = Http::get($img);
                $imgFile = $fileReq->body();

                if ($fileReq->status() == 200)
                {
                    $imgUpload = Http::asMultipart()->attach('organization_id', $adminUser->organization_id)->attach('image', $imgFile, generateRandomString() . '.jpg')->post(config('app.url') . '/api/images/upload');
                    try 
                    {
                        $data = $imgUpload->json();
                        $img = $data['image_url'];
                    }
                    catch (Exception $e) {
                        Log::channel('single')->error($img);
                    }
                }
            }

            // https://stackoverflow.com/a/3507472/9150867
            $itemImages = array_merge(
                array_slice(
                    array_diff(
                        array_filter(
                            array_unique($item['images']), 
                            function($img) {
                                if (strpos($img, 'harvardhoodie')) return false;
                                return true;
                            }
                        ),
                        $itemImages
                    ), 
                    0,
                    5 - count($itemImages)
                ),
                array_diff($itemImages, $item['images'])
            );
            $itemImages = array_filter($itemImages, function ($img) {
                return !is_null($img);
            });

            $upc = preg_replace("/[^0-9]/", "", $item['upc']);
            
            $itemData = [
                'created_by' => $createdBy->id,
                'organization_id' => $adminUser->organization_id,
                'classification_id' => $itemClassification->id,
                'condition_id' => $itemCondition->id,
                'title' => $item['title'],
                'description' => isset($item['description']) ? 
                    substr(
                        preg_replace(
                            "/[^a-zA-Z0-9_\- ]/", 
                            '',
                            $item['description']
                        ),
                        0, 
                        1000
                    ) 
                    : null,
                'price' =>  $itemPrice,
                'original_price' => isset($item['originalPrice']) ? $item['originalPrice'] * 100 : $itemPrice,
                'cost' => isset($item['cost']) ? $item['cost'] * 100 : null,
                'sku' => $item['sku'],
                'upc' => ($upc == "NaN" || strlen($upc) > 13 || strlen($upc) < 12 || !is_numeric($upc)) ? null : $upc,
                'asin' => (!isset($item['asin']) || is_numeric($item['asin'])) ? null : $item['asin'],
                'mpn' => isset($item['mpn']) ? $item['mpn'] : null,
                'merchant_name' => isset($item['merchant']) ? (is_array($item['merchant']) ? $item['merchant']['name'] : $item['merchant']) : null,
                'merchant_price' => isset($item['merchant']) && is_array($item['merchant']) ? (isset($item['merchant']['price']) ? $item['merchant']['price'] * 100 : null) : null,
                'weight' => isset($item['weight']) ? $item['weight'] : null,
                'brand' => isset($item['brand']) ? $item['brand'] : null,
                'color' => isset($item['color']) ? $item['color'] : null,
                'ean' => isset($item['ean']) ? $item['ean'] : null,
                'elid' => isset($item['elid']) ? $item['elid'] : null,
                'condition_description' => isset($item->conditionDescription) ? substr($item['conditionDescription'], 0, 1000) : null,
                'quantities' => array_filter($quantities),
                'images' => array_filter(array_slice($itemImages, 0, 5)),
                'mongo_id' => $item['_id']->__toString(),
                'created_at' => $item['createdAt']->toDateTime()->format('Y-m-d H:i:s')
            ];

            $req = Http::withToken($auth['data']['user']['token'])
                ->post(config('app.url') . '/api/items/create', $itemData);
            $data = $req->json();
            if ($data['success']) echo("Item created Item ID: " . $item['_id']->__toString() . "\n");
            else {
                echo("Failed to create Item Item ID: " . $item['_id']->__toString() . "\n");
                var_dump($item);
                var_dump($itemData);
                var_dump($req->body());
                Log::channel('single')->debug($item);
                Log::channel('single')->debug($itemData);
                Log::channel('single')->debug($req->body());
            }
        };
    }
}
