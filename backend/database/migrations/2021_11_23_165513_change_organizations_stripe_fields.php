<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOrganizationsStripeFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->renameColumn('card_brand', 'pm_type');
            $table->renameColumn('card_last_four', 'pm_last_four');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->renameColumn('pm_type', 'card_brand');
            $table->renameColumn('pm_last_four', 'card_last_four');
        });
    }
}
