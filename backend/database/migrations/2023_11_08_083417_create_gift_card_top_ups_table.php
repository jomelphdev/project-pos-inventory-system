<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftCardTopUpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gift_card_top_ups', function (Blueprint $table) {
            $table->id();
            $table->integer('amount');
            $table->integer('action')->comment('1-Funds added, 2-Item sold, 3-Item returned')->default(1);
            $table->foreignId('gift_card_id');
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
        Schema::dropIfExists('gift_card_top_ups');
    }
}
