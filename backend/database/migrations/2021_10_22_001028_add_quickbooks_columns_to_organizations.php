<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuickbooksColumnsToOrganizations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('quickbooks_realm_id')->nullable();
            $table->string('quickbooks_refresh_token', 512)->nullable();
            $table->string('quickbooks_access_token', 4096)->nullable();
            $table->boolean('is_quickbooks_authenticated')->default(false)->nullable();
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
            $table->dropColumn([
                'quickbooks_realm_id', 
                'quickbooks_refresh_token', 
                'quickbooks_access_token',
                'is_quickbooks_authenticated'
            ]);
        });
    }
}
