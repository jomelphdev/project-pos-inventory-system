<?php

namespace App\Console\Commands\LegacyMigrations;

use App\CustomClass\LegacyConnection;
use App\Models\Manifest;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class MigrateMongoManifest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongo-migration:manifest-items {user : MONGO_ID of the admin user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrates Manifest Items data from MongoDB to this DB';

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
        $user = User::where('mongo_id', $legacyUserId)->first();

        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $items = $conn->refItems()->find([], $options);
        
        try
        {
            DB::beginTransaction();

            $orgId = $user->organization_id;
            $manifest = Manifest::create([
                'organization_id' => $orgId,
                'manifest_name' => 'Manifest Migration'
            ]);

            $manifest->save();

            $items = array_map(function($i) use ($orgId) {
                $upc = substr(intval($i['upc']), 0, 13);

                return [
                    'organization_id' => $orgId,
                    'title' => $i['title'],
                    'description' => $i['description'],
                    'price' => $i['price'] * 100,
                    'quantity' => $i['expectedQuantity'],
                    'upc' => ($upc == "NaN" || strlen($upc) > 13 || strlen($upc) < 12 || !is_numeric($upc)) ? null : $upc,
                    'asin' => $i['asin'],
                    'mpn' => $i['mpn'],
                    'cost' => $i['cost'] * 100,
                    'fn_sku' => $i['fnSku'],
                    'lpn' => $i['lpn'],
                    'images' => count($i['images']) > 0 ? serialize($i['images']) : null
                ];
            }, $items->toArray());
            
            $manifest->manifestItems()->createMany($items);

            DB::commit();
            echo("Manifest Items created!\n");
        }
        catch (Exception $e)
        {
            var_dump($e->getMessage());
            DB::rollBack();
            echo("Failed to create Manifest Items.\n");
        }
    }
}
