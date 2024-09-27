<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuickbooksAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quickbooks_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id');
            $table->bigInteger('quickbooks_account_id', false, true);
            $table->enum('account_type', [
                'sales', 
                'cost', 
                'inventory_asset', 
                'cash',
                'sales_tax'
            ]);
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
        Schema::dropIfExists('quickbooks_accounts');
    }
}
