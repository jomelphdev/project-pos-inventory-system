<?php

use App\Models\Preference;
use App\Models\Store;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignOrganizationIdOnStores extends Migration
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

            foreach (Store::all() as $store)
            {
                $store->organization_id = Preference::without([
                    'owner', 
                    'classifications', 
                    'conditions', 
                    'discounts', 
                    'stores'
                ])->find($store->preference_id)->organization_id;
                $store->save();
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
