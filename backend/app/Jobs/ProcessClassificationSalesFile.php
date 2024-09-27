<?php

namespace App\Jobs;

use App\CustomClass\Reports\ClassificationSales;
use App\Events\FileCreated;
use App\Models\PosOrder;
use App\Models\Store;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use TypeError;

class ProcessClassificationSalesFile implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $store;
    private $orders;
    private $returns;
    private $startDate;
    private $endDate;
    private $userId;
    private $filename;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Store $store, array $dateRange)
    {   
        $this->store = $store;
        $this->startDate = $dateRange[0]->timezone($this->store->state()->first()->timezone)->startOfDay();
        $this->endDate = $dateRange[1]->timezone($this->store->state()->first()->timezone)->endOfDay();
        
        $this->orders = PosOrder::with('posOrderItems')
            ->where('store_id', $store->id)
            ->whereBetween('created_at', [$this->startDate->copy()->timezone('UTC'), $this->endDate->copy()->timezone('UTC')])
            ->get()
            ->all();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (count($this->orders) > 0)
        {
            $this->filename = $this->startDate->copy()->format('m-d-Y') . '_' .  $this->endDate->copy()->format('m-d-Y') . '_ClassificationSales.xlsx';
            $path = 'reports/' . $this->store->user_id . '/classification-sales/' . $this->store->id . '/' . $this->filename;
            
            $salesReport = new ClassificationSales($this->store, $this->orders, []);
            $spreadsheet = $salesReport->generateReport();

            try {
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                ob_start();
                $writer->save('php://output');
                $content = ob_get_contents();
                ob_clean();

                Storage::disk('s3')->put($path, $content);
            } catch (TypeError $e) {
                // No Sales Data
            }
        }
    }
}