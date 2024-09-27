<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuickbooksJournalEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quickbooks_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id');
            $table->bigInteger('quickbooks_journal_id', false, true);
            $table->date('for_date');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'for_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quickbooks_journal_entries');
    }
}
