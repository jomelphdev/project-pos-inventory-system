<?php

namespace App\Jobs;

use App\Events\LateReply;
use App\Exports\DailySalesExport;
use App\Models\Report;
use App\Models\Store;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessDailySalesFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60 * 60 * 15;

    private $storeId;
    private $store;
    private $date;
    private $userId;
    private $forStations;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($storeId, Carbon $date, $userId=null, $forStations=false)
    {
        if (config('app.env') != 'local') $this->queue = 'long_queue';
        
        $this->storeId = $storeId;
        $this->store = Store::find($storeId);
        $this->date = $date;
        // userId basically represents whether or not this is a manual request or a scheduled report.
        $this->userId = $userId;
        $this->forStations = $forStations;
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
            $cacheKey = 'processing:daily-sales:' . $this->storeId;
            Cache::put($cacheKey, true, now()->addMinutes(15));
            
            $fileName = generateRandomString() . '_DailySales.xlsx';
            $path = 'reports/' . $this->store->organization_id . '/daily_sales/' . $this->storeId . '/' . $fileName;

            (new DailySalesExport($this->storeId, $this->date, $this->forStations))->store($path, 's3');

            $dateRange = getDateRangeForReports($this->storeId, $this->date);
            Report::create([
                'organization_id' => $this->store->organization_id,
                'store_id' => $this->storeId,
                'file_name' => $fileName,
                'report_type' => 'daily_sales',
                'from_date' => $dateRange['start_date'],
                'to_date' => $dateRange['end_date']
            ]);

            if ($this->userId)
            {
                $fileName = $this->date->copy()->format('m/d/Y') . '_' . $this->store->name . '_DailySales.xlsx';

                event(new LateReply([
                    'success' => true,
                    'user_id' => $this->userId,
                    'message' => 'Your report: { ' . $fileName . ' } is ready for download!',
                    'storeId' => $this->storeId,
                    'fileName' => $fileName,
                    'date' => $this->date->copy()->format('m/d/Y'),
                    'reportType' => 'daily-sales'
                ]));
            }
        } 
        catch (Exception $e) 
        {
            Log::error($e->getMessage());
            if ($this->userId)
            {
                event(new LateReply([
                    'success' => false,
                    'user_id' => $this->userId,
                    'message' => 'No sales data.'
                ]));
            }
        }

        Cache::forget($cacheKey);
    }
}
