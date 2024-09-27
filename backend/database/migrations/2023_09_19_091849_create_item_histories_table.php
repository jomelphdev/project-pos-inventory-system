<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id');
            $table->foreignId('store_id');
            $table->integer('old_price');
            $table->integer('new_price')->nullable();
            $table->integer('old_original_price');
            $table->integer('new_original_price')->nullable();
            $table->integer('old_cost');
            $table->integer('new_cost')->nullable();
            $table->text('reason_for_change');
            $table->text('action')->comment('eg. add, update or remove etc.');
            $table->integer('created_by');
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
        Schema::dropIfExists('item_histories');
    }
}
