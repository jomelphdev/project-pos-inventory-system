<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreferenceOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preference_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id');
            $table->string("model_type", 255)->collation("utf8mb4_unicode_ci");
            $table->bigInteger('model_id', false, true);
            $table->string("key", 100);
            $table->string("value", 100)->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'model_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('preference_options');
    }
}
