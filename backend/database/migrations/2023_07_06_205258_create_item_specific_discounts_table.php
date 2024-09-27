<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("item_specific_discounts", function (Blueprint $table) {
			$table->id();
			$table->foreignId("item_id")->constrained();
			$table->unsignedSmallInteger("quantity");
			$table->unsignedDecimal("discount_amount", 14, 4);
			$table->enum("discount_type", ['amount', 'percent']);
            $table->unsignedTinyInteger('times_applicable')->nullable();
            $table->boolean('can_stack')->default(false);
            $table->timestamp('active_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
			$table->timestamps();
            $table->unique(['item_id', 'quantity']);
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("item_specific_discounts");
    }
};
