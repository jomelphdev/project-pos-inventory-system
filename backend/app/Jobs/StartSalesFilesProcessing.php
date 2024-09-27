<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartSalesFilesProcessing implements ShouldQueue
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
        $startDate = new Carbon('12/1/2020');
        $endDate = new Carbon('1/1/2021');

        /** @var User $user */
        foreach (User::query()->where('role', '!=', 'expired')->get()->all() as $user)
        {
            /** @var Store $store */
            foreach ($user->stores()->get()->all() as $store)
            {
                ProcessSalesFile::dispatch($store, [$startDate->copy()->endOfDay(), $endDate->copy()->endOfDay()]);
            }
        }

        return;
    }
}
