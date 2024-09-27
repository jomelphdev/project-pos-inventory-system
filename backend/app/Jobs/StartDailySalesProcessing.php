<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Store;
use Carbon\Carbon;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartDailySalesProcessing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $date = Carbon::now();

        /** @var User $user */
        foreach (User::query()->where('role', '!=', 'expired')->get()->all() as $user)
        {
            /** @var Store $store */
            foreach ($user->stores()->get()->all() as $store)
            {
                ProcessDailySalesFile::dispatch($store, $date);
            }
        }

        return;
    }
}
