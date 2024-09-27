<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsignmentFeeToPosTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->unsignedInteger('consignment_fee')->nullable();
        });

        Schema::table('pos_return_items', function (Blueprint $table) {
            $table->unsignedInteger('consignment_fee')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->dropColumn('consignment_fee');
        });

        Schema::table('pos_return_items', function (Blueprint $table) {
            $table->dropColumn('consignment_fee');
        });
    }
}
