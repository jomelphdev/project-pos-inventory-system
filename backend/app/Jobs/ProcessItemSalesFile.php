<?php

namespace App\Jobs;

use App\Events\LateReply;
use App\Exports\ItemSalesExport;
use App\Models\Report;
use App\Models\User;
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

class ProcessItemSalesFile implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60 * 60 * 15;

    private $user;
    private $orgId;
    private $start;
    private $end;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user=null, Carbon $start, Carbon $end=null)
    {
        if (config('app.env') != 'local') $this->queue = 'long_queue';
        
        $this->user = $user;
        $this->orgId = $user->organization_id;
        $this->start = $start;
        $this->end = $end;
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
            $cacheKey = 'processing:item-sales:' . $this->orgId;
            Cache::put($cacheKey, true, now()->addMinutes(15));

            $filename = generateRandomString() . '_Item-Sales.xlsx';
            $path = 'reports/' . $this->orgId . '/item-sales/' . $filename;

            (new ItemSalesExport($this->orgId, $this->start, $this->end))->store($path, 's3');

            Report::create([
                'organization_id' => $this->orgId,
                'file_name' => $filename,
                'report_type' => 'item-sales',
                'from_date' => $this->start,
                'to_date' => $this->end
            ]);

            if ($this->user)
            {
                event(new LateReply([
                    'success' => true,
                    'user_id' => $this->user->id,
                    'message' => 'Your report item sales report is ready for download!',
                    'fileName' => $filename,
                    'reportType' => 'item-sales'
                ]));
            }
        } 
        catch (Exception $e) 
        {
            Log::channel('single')->error($e);
            if ($this->user) 
            {
                event(new LateReply([
                    'success' => false,
                    'user_id' => $this->user->id,
                    'message' => 'Something went wrong while trying to process sales report.'
                ]));
            }
        }

        Cache::forget($cacheKey);
    }
}
