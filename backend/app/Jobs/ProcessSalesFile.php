<?php

namespace App\Jobs;

use App\Events\LateReply;
use App\Exports\SalesExport;
use App\Models\Report;
use App\Models\Store;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessSalesFile implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $storeId;
    private $store;
    private $startDate;
    private $endDate;
    private $userId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($storeId, Carbon $startDate, Carbon $endDate, int $userId=null)
    {   
        $this->storeId = $storeId;
        $this->store = Store::find($storeId);
        $this->userId = $userId;

        $dateRange = getDateRangeForReports($this->store->id, $startDate, $endDate);
        $this->startDate = $dateRange['start_date'];
        $this->endDate = $dateRange['end_date'];
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
            $cacheKey = 'processing:sales:' . $this->storeId;
            Cache::put($cacheKey, true, now()->addMinutes(15));

            $this->filename = generateRandomString() . '_Sales.xlsx';
            $path = 'reports/' . $this->store->organization_id . '/sales/' . $this->store->id . '/' . $this->filename;

            (new SalesExport([$this->storeId], $this->startDate, $this->endDate))->store($path, 's3');

            Report::create([
                'organization_id' => $this->store->organization_id,
                'store_id' => $this->store->id,
                'file_name' => $this->filename,
                'report_type' => 'sales',
                'from_date' => $this->startDate,
                'to_date' => $this->endDate
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
                    'message' => 'Something went wrong while trying to process sales report.'
                ]));
            }
        }

        Cache::forget($cacheKey);
    }
}
