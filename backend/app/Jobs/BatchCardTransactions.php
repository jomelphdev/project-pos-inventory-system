<?php

namespace App\Jobs;

use App\Models\PosOrder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class BatchCardTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $to = Carbon::createFromTime(19, 45, 0, 'America/New_York')->timezone('UTC');
        $from = $to->copy()->subDay();
        $orders = PosOrder::
            without('posOrderItems')
            ->select('processor_reference', 'organization_id')
            ->whereNotNull('processor_reference')
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->append('merchant_id');

        $batch = Bus::batch([])->dispatch();

        foreach ($orders as $order) 
        {
            $batch->add(new CaptureCardTransaction($order->merchant_id, $order->processor_reference));
        }
    }
}
