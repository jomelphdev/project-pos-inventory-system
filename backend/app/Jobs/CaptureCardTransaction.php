<?php

namespace App\Jobs;

use App\Contracts\ICardProcessor;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CaptureCardTransaction implements ShouldQueue
{
    
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $merchantId;
    private $referenceId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $merchantId, string $referenceId)
    {
        $this->merchantId = $merchantId;
        $this->referenceId = $referenceId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ICardProcessor $processor)
    {
        $processor->captureTransaction($this->merchantId, $this->referenceId);
    }
}
