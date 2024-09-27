<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("pos_order_items", function (Blueprint $table) {
			$table->foreignId("item_specific_discount_id")->nullable()->constrained();
            $table->unsignedSmallInteger("item_specific_discount_quantity")->nullable();
            $table->unsignedTinyInteger('item_specific_discount_times_applied')->nullable();
            $table->boolean('item_specific_discount_can_stack')->nullable();
            $table->unsignedDecimal("item_specific_discount_original_amount", 14, 4)->nullable();
            $table->unsignedDecimal("item_specific_discount_amount", 14, 4)->nullable();
			$table->enum("item_specific_discount_type", ['amount', 'percent'])->nullable();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("pos_order_items", function (Blueprint $table) {
            $table->dropConstrainedForeignId('item_specific_discount_id');
            $table->dropColumn("item_specific_discount_quantity");
            $table->dropColumn('item_specific_discount_times_applied');
            $table->dropColumn('item_specific_discount_can_stack');
            $table->dropColumn('item_specific_discount_original_amount');
            $table->dropColumn('item_specific_discount_amount');
            $table->dropColumn('item_specific_discount_type');
        });
    }
};
