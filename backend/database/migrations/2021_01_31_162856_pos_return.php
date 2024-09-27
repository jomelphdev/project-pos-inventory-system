<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PosReturn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pos_returns', function (Blueprint $table) {
            $table->id();
            $table->string('mongo_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('no action');
            $table->foreignId('organization_id')->constrained()->onDelete('no action');
            $table->foreignId('store_id')->constrained()->onDelete('no action');
            $table->foreignId('pos_order_id')->constrained()->onDelete('no action');
            $table->unsignedBigInteger('cash');
            $table->unsignedBigInteger('card');
            $table->unsignedBigInteger('ebt');
            $table->unsignedBigInteger('sub_total');
            $table->unsignedBigInteger('tax');
            $table->unsignedBigInteger('total');
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
        Schema::drop('pos_returns');
    }
}
