<?php

use App\Models\Classification;
use App\Models\Preference;
use App\Models\PreferenceOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePreferenceOptionsForDeletedClassifications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try
        {
            DB::beginTransaction();
            
            foreach (Classification::withTrashed()->whereNotNull("deleted_at")->get() as $classificaiton)
            {
                $storeIds = Preference::without([
                    'owner', 
                    'classifications', 
                    'conditions', 
                    'discounts'
                ])->find($classificaiton->preference_id)->stores()->get()->pluck('id');

                foreach ($storeIds as $storeId)
                {
                    PreferenceOption::create([
                        "store_id" => $storeId,
                        "model_id" => $classificaiton->id,
                        "model_type" => "App\Models\Classification",
                        "key" => "is_ebt",
                        "value" => (bool) $classificaiton->is_ebt,
                    ]);
                    PreferenceOption::create([
                        "store_id" => $storeId,
                        "model_id" => $classificaiton->id,
                        "model_type" => "App\Models\Classification",
                        "key" => "is_taxed",
                        "value" => (bool) $classificaiton->is_taxed,
                    ]);
                }
            }

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
