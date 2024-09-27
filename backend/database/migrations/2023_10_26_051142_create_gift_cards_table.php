<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('gift_code', 100)->unique(); 
            $table->string('title', 100);
            $table->string('description', 100);
            $table->integer('is_activated')->comment('1-activated, 0-deactivated')->default(1);
            $table->integer('balance')->default(0);
            $table->date('expiration_date');
            $table->integer('created_by');
            $table->foreignId('organization_id');
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
        Schema::dropIfExists('gift_cards');
    }
}
