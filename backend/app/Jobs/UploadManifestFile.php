<?php

namespace App\Jobs;

use App\Events\LateReply;
use App\Models\Manifest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadManifestFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $manifest;
    private $sheet;
    private $userId;
    private $items;
    private $columns;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Manifest $manifest, UploadedFile $file, $userId=null)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $this->sheet = $reader->load($file->getPathname());
        $this->manifest = $manifest;
        $this->userId = $userId;
        $this->items = $this->sheet->getActiveSheet()->toArray();
        $this->columns = [];
       
        foreach ($this->items[0] as $index=>$val)
        {
            $this->columns[strtolower($val)] = $index;
        }

        array_shift($this->items);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mappedItems = array_map(function ($i) {
            $upc = strval($i[$this->columns['upc']]);
            $upc = str_pad($upc, 12, "0", STR_PAD_LEFT);
            return [
                'organization_id' => $this->manifest->organization_id,
                'title' => $i[$this->columns['title']],
                'price' => $i[$this->columns['price']] * 100,
                'cost' => $i[$this->columns['cost']] * 100,
                'description' => $i[$this->columns['description']],
                'images' => $i[$this->columns['images']],
                'asin' => $i[$this->columns['asin']],
                'upc' => $upc,
                'fn_sku' => $i[$this->columns['fn_sku']],
                'lpn' => $i[$this->columns['lpn']],
                'mpn' => $i[$this->columns['mpn']]
            ];
        }, $this->items);
        
        $this->manifest->manifestItems()->createMany($mappedItems);

        if ($this->userId)
        {
            LateReply::dispatch([
                'success' => true,
                'user_id' => $this->userId,
                'message' => 'Manifest ' . $this->manifest->manifest_name . ' has been processed successfully. Items should appear in manifest tab.',
                'response_type' => 'manifest-uploaded'
            ]);
        }
    }
}
