<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDiscountDecimalCheckConstraint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE discounts ADD CONSTRAINT discounts_check_discount_percentage CHECK (discount <= 1);');
        DB::statement('ALTER TABLE pos_order_items ADD CONSTRAINT pos_order_items_check_discount_percentage CHECK (discount_percent <= 1);');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE discounts DROP CONSTRAINT discounts_check_discount_percentage;');
        DB::statement('ALTER TABLE pos_order_items DROP CONSTRAINT pos_order_items_check_discount_percentage;');
    }
}
