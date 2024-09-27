<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCashDrawerColumnsToCheckoutStations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('checkout_stations', function (Blueprint $table) {
            $table->bigInteger('drawer_balance')->nullable();
            $table->timestamp('last_balanced')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('checkout_stations', function (Blueprint $table) {
            $table->dropColumn('drawer_balance');
            $table->dropColumn('last_balanced');
        });
    }
}
