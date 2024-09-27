<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('classifications', function (Blueprint $table) {
            $table->id();
            $table->string('mongo_id')->nullable();
            $table->foreignId('preference_id')->constrained()->onDelete('no action');
            $table->string('name', 50);
            $table->unsignedDecimal('discount', 4, 4)->default('0')->nullable();
            $table->boolean('is_ebt')->default(false);
            $table->boolean('is_taxed')->default(true);
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
        Schema::dropIfExists('classifications');
    }
}
