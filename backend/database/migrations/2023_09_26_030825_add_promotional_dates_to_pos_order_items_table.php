<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPromotionalDatesToPosOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->date('item_specific_discount_active_at')->nullable();
            $table->date('item_specific_discount_expires_at')->nullable();
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
            $table->dropColumn('item_specific_discount_active_at');
            $table->dropColumn('item_specific_discount_expires_at');
        });
    }
}
