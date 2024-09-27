<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddedItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('added_items', function (Blueprint $table) {
            $table->id();
            $table->string('mongo_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('no action');
            $table->foreignId('organization_id')->constrained()->onDelete('no action');
            $table->foreignId('classification_id')->constrained()->onDelete('no action');
            $table->string('title');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('original_price');
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
        Schema::dropIfExists('added_items');
    }
}
