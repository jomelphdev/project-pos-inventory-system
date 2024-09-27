<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedAtColumns extends Migration
{

    private $tables= [
        'items', 'pos_orders', 'quantities', 
        'item_images', 'classifications', 'conditions', 
        'discounts', 'users', 'stores'
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $table)
        {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tables as $table)
        {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
        
    }
}
