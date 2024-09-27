<?php

namespace App\Jobs;

use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAllUsersDailyQuickBooksJournalEntry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var Organiztion */
    private $organizations;
    private $date;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
{
        $this->organizations = Organization::whereNotNull('quickbooks_realm_id');
        
        $now = Carbon::now();
        $this->date = $now->subDay();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->organizations as $org)
        {
            if (!$org->subscription_required)
            {
                ProcessUsersQuickBooksJournalEntry::dispatch($org, $this->date);
            }
        }
    }
}
