<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('mongo_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('no action');
            $table->foreignId('organization_id')->constrained()->onDelete('no action');
            $table->foreignId('classification_id')->constrained()->onDelete('no action');
            $table->foreignId('condition_id')->constrained()->onDelete('no action');
            $table->foreignId('manifest_item_id')->nullable()->constrained()->onDelete('no action');
            $table->string('title', 200);
            $table->string('description', 1000)->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('original_price');
            $table->unsignedBigInteger('cost')->nullable();
            $table->string('sku', 10);
            $table->string('upc', 13)->index()->nullable();
            $table->string('asin', 10)->index()->nullable();
            $table->string('mpn', 100)->index()->nullable();
            $table->string('merchant_name')->nullable();
            $table->unsignedBigInteger('merchant_price')->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('color', 100)->nullable();
            $table->string('ean', 13)->nullable();
            $table->string('elid', 12)->nullable();
            $table->string('condition_description', 1000)->nullable();
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
        Schema::dropIfExists('items');
    }
}
