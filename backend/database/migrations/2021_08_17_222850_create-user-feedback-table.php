<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserFeedbackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_feedback', function(Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('no action');;
            $table->foreignId('user_id')->constrained()->onDelete('no action');;
            $table->string('prompt');
            $table->string('feedback');
            $table->string('origin');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_feedback');
    }
}
