<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('mongo_id')->nullable();
            $table->foreignId('preference_id')->constrained()->onDelete('no action');
            $table->foreignId('receipt_option_id')->constrained()->onDelete('no action');
            $table->foreignId('state_id')->constrained()->onDelete('no action');
            $table->string('city', 20);
            $table->string('address');
            $table->string('zip', 20);
            $table->string('name');
            $table->string('phone', 10);
            $table->unsignedDecimal('tax_rate', 4, 4);
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
        Schema::dropIfExists('stores');
    }
}
