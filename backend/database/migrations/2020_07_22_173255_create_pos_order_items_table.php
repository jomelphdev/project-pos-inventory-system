<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePosOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pos_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')->constrained()->onDelete('no action');
            $table->foreignId('item_id')->nullable()->constrained()->onDelete('no action');
            $table->foreignId('added_item_id')->nullable()->constrained()->onDelete('no action');
            $table->foreignId('discount_id')->nullable()->constrained()->onDelete('no action');
            $table->unsignedBigInteger('price');
            $table->unsignedDecimal('discount', 4, 4)->nullable();
            $table->unsignedSmallInteger('quantity_ordered');
            $table->boolean('is_ebt')->default(false);
            $table->boolean('is_taxed')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pos_order_items');
    }
}
