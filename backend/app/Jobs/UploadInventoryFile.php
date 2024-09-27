<?php

namespace App\Jobs;

use App\Events\LateReply;
use App\Models\Item;
use App\Models\Store;
use App\Models\User;
use App\Services\ItemService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UploadInventoryFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $sheet;
    private $columns;
    private $completedIds;
    private $user;
    private $orgId;
    private $classifications;
    private $conditions;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(UploadedFile $file, User $user)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $this->sheet = $reader->load($file->getPathname());
        $this->columns = [];
        $this->completedIds = [];
        $this->user = $user;
        
        $preferences = $this->user->preferences;
        
        $this->orgId = $this->user->organization_id;
        $this->classifications = $preferences->classifications()->select('id', 'name')->get();
        $this->conditions = $preferences->conditions()->select('id', 'name')->get();

        foreach ($this->sheet->getActiveSheet()->toArray()[0] as $index=>$val)
        {
            $this->columns[strtolower($val)] = $index;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $storeNames = $this->sheet->getSheetNames();

        foreach ($storeNames as $storeName)
        {
            $store = Store::where([
                ['organization_id', $this->orgId],
                ['name', $storeName]
            ])->first();
            $worksheet = $this->sheet->getSheetByName($storeName);
            $rows = $worksheet->toArray();
            
            // Remove Headers
            array_shift($rows);

            foreach ($rows as $row)
            {
                $this->createItem($row, $store->id);
            }
        }
        
        LateReply::dispatch([
            'success' => true,
            'user_id' => $this->user->id,
            'message' => 'Inventory file was successfully processed, the items should appear under the "Items" tab.',
            'response_type' => 'inventory-uploaded'
        ]);
    }

    private function createItem($row, $storeId)
    {
        // Classification & Condition are entered by name, this maps name to id.
        $classification = $row[$this->columns['classification']];
        $condition = $row[$this->columns['condition']];
        $classificationId = $classification 
            ? $this->classifications->where('name', $classification)->first()->id
            : null;
        $conditionId = $condition
            ? $this->conditions->where('name', $condition)->first()->id
            : null;

        $mappedItem = [
            'organization_id' => $this->orgId,
            'classification_id' => $classificationId,
            'condition_id' => $conditionId,
        ];

        // Auto-map the columns
        foreach ($this->columns as $column=>$index)
        {
            $mappedItem[$column] = $row[$index];
        }

        // Tweak to fit backend expectation.
        $upc = strval($mappedItem['upc']);
        $mappedItem['upc'] = str_pad($upc, 12, "0", STR_PAD_LEFT);
        $mappedItem['price'] = (int) $mappedItem['price'] * 100;
        $mappedItem['original_price'] = $mappedItem['price'];
        $mappedItem['cost'] = (int) $mappedItem['cost'] * 100;
        $mappedItem['images'] = !is_null($mappedItem['images']) 
            ? explode(',', str_replace(' ', '', $mappedItem['images']))
            : null;
        $mappedItem['quantities'] = [
            [
                'store_id' => $storeId,
                'created_by' => $this->user->id,
                'quantity_received' => $mappedItem['quantity'],
                'message' => 'Quantity from inventory import.'
            ]
        ];

        $item = $this->checkIfItemWasPreviouslyProcessed($mappedItem);

        if ($item)
        {
            return $item->quantities()->createMany($mappedItem['quantities']);
        }

        $itemService = new ItemService;
        $newItem = $itemService->createItem($mappedItem, $this->user);

        array_push($this->completedIds, $newItem->id);
    }

    private function checkIfItemWasPreviouslyProcessed($item)
    {
        $hasUpc = !is_null($item['upc']);
        $queryType = $hasUpc
            ? 'upc'
            : 'title';

        $item = Item::where($queryType, $item[$queryType])->first();

        if ($item && in_array($item->id, $this->completedIds))
        {
            return $item;
        }

        return false;
    }
}
