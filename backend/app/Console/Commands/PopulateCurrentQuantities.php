<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Organization;
use App\Models\User;
use App\Services\QuantityService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PopulateCurrentQuantities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:current-quantities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs quantity calculations on all current items, and creates or updates a record in current_quantities table.';

    private $qs;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->qs = new QuantityService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $negatives = 0;
        $lastSeenId = 0;
        $limit = 250;
        $itemsLeft = true;

        try
        {
            DB::beginTransaction();

            while ($itemsLeft == true)
            {
                $items = Item::where('id', '>', $lastSeenId)->limit($limit)->get();
                $lastSeenId = $items->last()->id;
                $items->each(function (Item $item) use (&$negatives) {
                    $stores = $item->storeIdsWithQty();
                    foreach ($stores as $storeId)
                    {
                        try
                        {
                            $this->qs->setCurrentQuantity($storeId, $item->id);
                        }
                        catch (QueryException $e)
                        {
                            $qtyToAdd = $this->qs->calculateCurrentQuantity($storeId, $item->id);
                            if ($qtyToAdd >= 0) continue;
                            
                            $negatives += 1;
                            $createdBy = User::select('id')->where('organization_id', $item->organization->id)->role('owner')->first()->id;
                            $item->quantities()->create([
                                'store_id' => $storeId,
                                'created_by' => $createdBy,
                                'quantity_received' => abs($qtyToAdd),
                                'message' => 'RetailRight system correcting negative quantity correction to 0.'
                            ]);

                            $this->qs->setCurrentQuantity($storeId, $item->id);
                        }
                    }
                });

                if ($items->count() < $limit)
                {
                    $itemsLeft = false;
                }
            }

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            throw $e;
        }

        if ($negatives > 0) 
        {
            $itemCount = Item::count();
            $percent = $negatives / $itemCount;
            Log::error('Found ' . $negatives . ' items with negative quantity out of ' . $itemCount . ' items. (' . $negatives . '/' . $itemCount. ') ' . $percent);
        }
    }
}
