<?php

namespace App\Jobs;

use App\Events\LateReply;
use App\Exports\InventoryExport;
use App\Models\Report;
use App\Models\User;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessInventoryReport implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60 * 60 * 15;
    public $tries = 1;

    private $user;
    private $orgId;
    private $stores;
    private $withEmptyQuantities;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, array $stores, bool $withEmptyQuantities=false)
    {
        if (config('app.env') != 'local') $this->queue = 'long_queue';
        
        $this->user = $user;
        $this->orgId = $user->organization_id;
        $this->stores = $stores;
        $this->withEmptyQuantities = $withEmptyQuantities;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try
        {
            $cacheKey = "processing:inventory:" . $this->orgId;
            Cache::put($cacheKey, true, now()->addMinutes(15));

            $filename = generateRandomString() . '_Inventory.xlsx';
            $path = 'reports/' . $this->orgId . '/inventory/' . $filename;

            (new InventoryExport($this->stores, $this->user->id, $this->withEmptyQuantities))->store($path, 's3');
            Report::create([
                'organization_id' => $this->orgId,
                'report_type' => 'inventory',
                'file_name' => $filename
            ]);
            
            event(new LateReply([
                'success' => true,
                'user_id' => $this->user->id,
                'message' => 'Your report is ready for download!',
                'reportType' => 'inventory',
                'response_type' => 'file-finished'
            ]));
        }
        catch (Exception $e)
        {
            Log::error($e->getMessage());
            event(new LateReply([
                'success' => false,
                'user_id' => $this->user->id,
                'message' => 'Something went wrong while processing inventory report.',
                'reportType' => 'inventory',
                'response_type' => 'file-finished'
            ]));
        }

        Cache::forget($cacheKey);
    }
}
