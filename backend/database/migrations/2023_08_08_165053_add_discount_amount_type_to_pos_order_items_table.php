<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountAmountTypeToPosOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->enum('discount_amount_type', ['price', 'total', 'order_total'])->nullable();
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
            $table->dropColumn('discount_amount_type');
        });
    }
}
