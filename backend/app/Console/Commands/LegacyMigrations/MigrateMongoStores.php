<?php

namespace App\Console\Commands\LegacyMigrations;

use App\CustomClass\LegacyConnection;
use App\Models\ReceiptOption;
use App\Models\State;
use App\Models\Store;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class MigrateMongoStores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "mongo-migration:stores";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Migrates Stores data from MongoDB to this DB";

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
        $conn = new LegacyConnection("");
        $stores = $conn->stores()->find();
        
        DB::beginTransaction();
        foreach ($stores as $store)
        {
            $id = $store->_id->__toString();

            if (Store::where('mongo_id', $id)->first()) continue;

            try
            {
                $storeState = State::where("abbreviation", $store->state)->first();
                $legacyUserId = $store->userId;
                $user = User::where("mongo_id", $legacyUserId)->firstOrFail();
                $preferenceId = $user->organization()
                    ->first()
                    ->preferences
                    ->id;
                $legacyOptions = $store->receiptOptions;
                $receiptOption = new ReceiptOption();
                $receiptOption->fill([
                    "preference_id" => $preferenceId,
                    "name" => $legacyOptions->name,
                    "image_url" => isset($legacyOptions->storeLogo) ? $legacyOptions->storeLogo : null,
                    "footer" => $legacyOptions->footer
                ]);
                $receiptOption->save();

                $storeData = [
                    "preference_id" => $preferenceId,
                    "organization_id" => $user->organization_id,
                    "receipt_option_id" => $receiptOption->id,
                    "state_id" => $storeState->id,
                    "city" => $store->city,
                    "address" => $store->address,
                    "zip" => $store->zipcode,
                    "name" => $store->name,
                    "phone" => str_replace('-', '', $store->phone),
                    "tax_rate" => $store->taxRate,
                    "mongo_id" => $id
                ];
                $newStore = new Store();
                $newStore->fill($storeData);
                $newStore->save();

                echo ("Store created Legacy ID: " . $legacyUserId . " Store ID: " . $id . "\n");
            }
            catch (ModelNotFoundException $e)
            {
                echo ("User does not exist Legacy ID: " . $legacyUserId . "\n");
                continue;
            }
            catch (Exception $e)
            {
                DB::rollBack();
                // var_dump($store);
                echo($e->getMessage() . " Legacy ID: " . $legacyUserId . " Store ID: " . $id . "\n");
            }
        }
        DB::commit();
    }
}
