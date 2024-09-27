<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToPosReturnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_returns', function (Blueprint $table) {
            $table->index('checkout_station_id', 'pos_returns_checkout_station_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pos_returns', function (Blueprint $table) {
            $table->dropIndex('pos_returns_checkout_station_id_foreign');
        });
    }
}
