<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceiptOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('receipt_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('preference_id')->constrained()->onDelete('no action');
            $table->string('name');
            $table->string('image_url', 2000)->nullable();
            $table->string('footer', 2000)->nullable();
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
        Schema::dropIfExists('receipt_options');
    }
}
