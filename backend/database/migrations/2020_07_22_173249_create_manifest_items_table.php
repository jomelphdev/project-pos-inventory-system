<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManifestItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manifest_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('no action');
            $table->foreignId('manifest_id')->constrained()->onDelete('no action');
            $table->string('title')->index();
            $table->string('description', 1000)->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('quantity')->nullable();
            $table->string('upc', 13)->index()->nullable();
            $table->string('asin', 10)->index()->nullable();
            $table->string('mpn', 100)->index()->nullable();
            $table->unsignedBigInteger('cost')->nullable();
            $table->string('fn_sku', 20)->nullable();
            $table->string('lpn', 20)->nullable();
            $table->string('images')->nullable();
            $table->unsignedInteger('quantity_expected')->nullable();
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
        Schema::dropIfExists('manifest_items');
    }
}
