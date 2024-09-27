<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PosReturnItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pos_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_return_id')->constrained()->onDelete('no action');
            $table->foreignId('pos_order_item_id')->constrained()->onDelete('no action');
            $table->foreignId('item_id')->nullable()->constrained()->onDelete('no action');
            $table->unsignedSmallInteger('quantity_returned');
            $table->unsignedTinyInteger('action')->nullable();
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
        Schema::dropIfExists('pos_return_items');
    }
}
