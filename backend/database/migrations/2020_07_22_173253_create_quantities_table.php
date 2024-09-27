<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuantitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('no action');
            $table->foreignId('store_id')->constrained()->onDelete('no action');
            $table->foreignId('created_by')->constrained('users')->onDelete('no action');
            $table->smallInteger('quantity_received');
            $table->string('message');
            $table->unsignedMediumInteger('manifest_number')->nullable();
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
        Schema::dropIfExists('quantities');
    }
}
