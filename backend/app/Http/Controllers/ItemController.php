<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculatePriceRequest;
use App\Http\Requests\GetBySkuRequest;
use App\Http\Requests\GetByUpcRequest;
use App\Http\Requests\ItemStoreRequest;
use App\Http\Requests\ItemUpdateRequest;
use App\Http\Requests\QueryItemsRequest;
use App\Http\Resources\Item as ResourcesItem;
use App\Http\Resources\ItemCollection;
use App\Http\Resources\ItemHistoryResource;
use App\Jobs\UploadInventoryFile;
use App\Models\Item;
use App\Models\Organization;
use App\Models\CurrentQuantity;
use App\Models\ItemHistory;
use App\Services\ItemPricing;
use App\Services\ItemService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;


use Money\Money;

class ItemController extends Controller
{
    /**
     * @param Integer $organization_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($orgId)
    {
        $items = Item::all()->where('organization_id', $orgId);

        return response()->success([
            'items' => new ItemCollection($items)
        ]);
    }

    /**
     * @param \App\Http\Requests\ItemStoreRequest $request
     * @param \App\Item $item
     * @return \Illuminate\Http\JsonResponse
     */

    public function store(ItemStoreRequest $request, ItemService $itemService)
    {
        $data = $request->validated();
        $user = $request->user();

        $this->authorize('create', Item::class);

        try
        {
            DB::beginTransaction();

            $item = $itemService->createItem($data, $user);
            $item->append('store_quantities');

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->error('Something went wrong while trying to create item.');
        }

        return response()->success(['item' => new ResourcesItem($item)]);
    }

    /**
     * @param Integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Item $item, $id)
    {
        try
        {
            $item = $item->with('itemSpecificDiscounts')->findOrFail($id);
            $item->append(['store_quantities', 'quantity_log']);
        }
        catch (ModelNotFoundException $e)
        {
            return $this->itemDoesNotExistResponse();
        }

        $this->authorize('view', $item);

        return response()->success(['item' => new ResourcesItem($item)]);
    }

    /**
     * @param \App\Http\Requests\ItemUpdateRequest $request
     * @param \App\Item $item
     * @param Integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ItemUpdateRequest $request, Item $item, $id)
    {
        $data = $request->validated();

        try
        {
            $item = $item->query()
                        ->with(['quantities'])
                        ->findOrFail($id)
                        ->append('store_quantities');
            $originalItem = $item->toArray();
        }
        catch (ModelNotFoundException $e)
        {
            return $this->itemDoesNotExistResponse();
        }

        $this->authorize('update', $item);

        try
        {
            DB::beginTransaction();
            
            $item->fill($data);

            if ($request->has('quantities'))
            {
                $item->quantities()->createMany($data['quantities']);
            }
            if ($request->has('images'))
            {
                foreach ($data['images'] as $image)
                {
                    $item->itemImages()->create(['image_url' => $image]);
                }
            }
            if ($request->has('deleted_images'))
            {
                foreach ($data['deleted_images'] as $image)
                {
                    $item->itemImages()->where(['image_url' => $image])->delete();
                    if (str_contains($image, 'retail-right'))
                    {
                        $strArr = explode('/', $image);
                        $arrLength = count($strArr);
                        Storage::disk('s3')->delete($strArr[$arrLength-2] . '/' . $strArr[$arrLength-1]);
                    }
                }
            }
            if ($request->has('specific_discounts'))
            {
                $discounts = array_map(function ($i) { 
                    unset($i['discount_description']);
                    return $i;
                }, $data['specific_discounts']);

                $newDiscounts = array_filter($discounts, function ($discount) {
                    return !isset($discount['id']);
                });
                $existingDiscounts = array_filter($discounts, function ($discount) {
                    return isset($discount['id']);
                });

                if (count($newDiscounts) > 0) $item->itemSpecificDiscounts()->createMany($newDiscounts);
                if (count($existingDiscounts) > 0) $item->itemSpecificDiscounts()->upsert($existingDiscounts, ['id'], ['quantity', 'discount_type', 'discount_amount', 'times_applicable', 'can_stack', 'active_at', 'expires_at', 'deleted_at']);
            }

            if ($item->isDirty('price') || $item->isDirty('original_price') || $item->isDirty('cost'))
            {
                foreach ($item->storeIdsWithQty() as $id) {
                    $itemHistoryData = [
                        "item_id" => $item->id,
                        "store_id" => $id,
                        "old_price" => $originalItem['price'], 
                        "new_price" => $item->price, 
                        "old_original_price" => $originalItem['original_price'], 
                        "new_original_price" => $item->original_price, 
                        "old_cost" => $originalItem['cost'], 
                        "new_cost" => $item->cost, 
                        "reason_for_change" => $request->reason ? $request->reason : "",
                        "action" => 'update',
                        "created_by" => $request->user()->id,
                    ];
    
                    $item->itemHistory()->create($itemHistoryData);
                }
            }

            $item->save();

            DB::commit();
        }
        catch (Throwable $e)
        {
            Log::error($e->getMessage());
            DB::rollBack();
            return response()->error('Something went wrong while trying to save item.');
        }

        return response()->success(['item' => new ResourcesItem($item->fresh('itemSpecificDiscounts')->append('store_quantities'))]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Item $item
     * @param Integer $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Item $item, $id)
    {        
        $item = $item->find($id);
        $this->authorize('update', $item);

        try
        {
            DB::beginTransaction();

            $item->delete();

            DB::commit();
        }
        catch (Throwable $e)
        {
            DB::rollBack();
            return response()->error('Something went wrong deleting the item');
        }

        return response()->success(['message' => 'Item deleted: ' . $item->title]);
    }

    public function importInventory(Request $request)
    {
        $request->validate(['inventory_file' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/excel']);

        try
        {
            UploadInventoryFile::dispatch($request->file('inventory_file'), $request->user());
        }
        catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to upload inventory file.');
        }

        return response()->success([
            'message' => 'You will get a notification when we have finished processing the file.'
        ]);
    }

    /**
     * Query all items for a user and return items where the query is any part of 'sku', 'upc', or 'title' strings.
     * @param \App\Http\Requests\QueryItemsRequest $request
     * @return \Illuminate\Http\JsonResponse returns Items in "response->data" array field.
     */
    public function queryItems(QueryItemsRequest $request)
    {
        $orgId = $request->user()->organization_id;
        $query = $request->input('query', '');
        $lastSeenId = $request->input('last_seen_id');

        $this->authorize('query', Item::class);

        $items = Item::basicRegexQuery($orgId, $query, 30, $lastSeenId)->get();

        return response()->success([
            'items' => ResourcesItem::collection($items)
        ]);
    }

    public function queryItemsCount(Request $request)
    {
        $orgId = $request->user()->organization_id;
        $query = $request->input('query', '');

        $this->authorize('query', Item::class);

        $queryCount = Item::basicRegexQuery($orgId, $query)->get()->count();

        return response()->success(['items_count' => (int) $queryCount]);
    }

    public function getUsedConditionsFromTitle(Request $request)
    {
        $orgId = $request->user()->organization_id;
        $query = $request->input('title', '');

        $this->authorize('query', Item::class);

        $items = Item::without('images')
            ->where('organization_id', $orgId)
            ->where('title', $query)
            ->select('condition_id')
            ->get();
        
        return response()->success(['used_conditions' => $items->pluck('condition_id')]);
    }

    public function queryItemsForShowcase(QueryItemsRequest $request)
    {
        $orgId = Organization::where('slug', $request->input('slug'))->first()->id;
        $query = $request->input('query', '');
        $storeId = $request->input('store_id', false);
        $classificationId = $request->input('classification_id', false);
        $conditionId = $request->input('condition_id', false);
        $page = $request->input('page', 1);

        $baseQuery = Item::basicRegexQuery($orgId, $query)->without('createdBy');

        if (is_numeric($classificationId)) 
        {
            $baseQuery = $baseQuery->where('classification_id', $classificationId);
        }

        if (is_numeric($conditionId)) 
        {
            $baseQuery = $baseQuery->where('condition_id', $conditionId);
        }

        if (is_numeric($storeId)) 
        {
            $baseQuery = $baseQuery->whereHas('quantities', function (Builder $query) use ($storeId) {
                $query->where('store_id', $storeId);
            });
        }

        // $items = $baseQuery->get()->each;
        $items = $baseQuery->paginate(30, ['*'], 'page', $page);

        return response()->success([
            'current_page' => $items->currentPage(),
            'items' => ResourcesItem::collection($items->append('store_quantities')),
            'to' => $items->lastItem(),
            'total' => $items->total()
        ]);
    }

    /**
     * Query specific SKU for user.
     * @param \App\Http\Requests\GetBySkuRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBySku(GetBySkuRequest $request)
    {
        $this->authorize('query', Item::class);
        $orgId = $request->user()->organization_id;

        $item = Item::query()
            ->where([
                ['organization_id', $orgId],
                ['sku', $request->sku]
            ])
            ->first();
        
        if (!$item)
        {
            return $this->itemDoesNotExistResponse();
        }

        $item->append('store_quantities');
        return response()->success(['item' => new ResourcesItem($item)]);
    }

    /**
     * Get existing items via UPC.
     */
    public function getByUpc(Request $request)
    {
        $request->validate([
            'upc' => 'string|min:12|max:13|regex:/^[0-9]+$/|required',
            'options.with_quantities' => 'boolean|nullable',
            'options.only_for_store_id' => 'integer|nullable'
        ]);
        $this->authorize('query', Item::class);
        $orgId = $request->user()->organization_id;
        $options = $request->input('options');
        $storeId = isset($options['only_for_store_id'])
            ? $options['only_for_store_id']
            : null;

        $sqlStatement = "
        SELECT *
        FROM 
            (
                SELECT i.*,
                    (
                        COALESCE((
                            SELECT SUM(q.quantity_received)
                            FROM quantities AS q
                            WHERE i.id=q.item_id" . ($storeId ? " AND q.store_id=" . $storeId : '') . 
                        "), 0) + 
                        COALESCE((
                            SELECT SUM(ri.quantity_returned)
                            FROM pos_return_items AS ri
                            LEFT JOIN pos_returns as pr ON ri.pos_return_id=pr.id
                            WHERE i.id=ri.item_id AND action=1" . ($storeId ? " AND pr.store_id=" . $storeId : '') .
                        "), 0) -
                        COALESCE((
                            SELECT SUM(oi.quantity_ordered)
                            FROM pos_order_items AS oi
                            LEFT JOIN pos_orders AS po ON oi.pos_order_id=po.id
                            WHERE i.id=oi.item_id" . ($storeId ? " AND po.store_id=" . $storeId : '') .
                        "), 0)
                    ) AS quantity
                FROM items AS i
            ) AS i
        WHERE i.upc=? AND i.organization_id=?;
        ";
        $query = DB::select($sqlStatement, [$request->upc, $orgId]);
        $items = Item::hydrate($query);

        if (isset($options['with_quantities']) && $options['with_quantities'])
        {
            $items->append('store_quantities');
        }
        
        return response()->success(['items' => ResourcesItem::collection($items)]);
    }

    /**
     * Get UPC data from UPC DB to create new items.
     * @param \App\Http\Requests\GetByUpcRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUpcData(GetByUpcRequest $request)
    {
        $upc = $request->upc;
        $orgId = $request->user()->organization_id;
        $this->authorize('query', Item::class);
            
        $req = Http::withHeaders(['user_key' => config('services.upc.key')])
            ->get('https://api.upcitemdb.com/prod/v1/lookup?upc=' . $upc)
            ->onError(function () {
                return response()->error('Error trying to reach UPC database.');
            });
        
        $body = $req->json();
        $upcItem = isset($body['items']) && count($body['items']) > 0 
            ? $body['items'][0] 
            : null;

        $items = Item::query()
            ->where([
                ['organization_id', $orgId],
                ['upc', $upc]
            ])
            ->get();

        switch ($req->status())
        {
            case 200:
                if ($upcItem && isset($upcItem['offers']) && count($upcItem['offers']) > 0)
                {
                    if (count($upcItem['offers']) > 1)
                    {
                        $allOffersTotaled = array_sum(array_column($upcItem['offers'], 'price'));
                        $avg = $allOffersTotaled / count($upcItem['offers']);
                        array_push($upcItem['offers'], [
                            'merchant' => 'Average Price', 
                            'price' => $avg, 
                            'link' => 'avg',
                            'updated_t' => now()->timestamp
                        ]);
                    }

                    usort($upcItem['offers'], function ($a, $b) {
                        return $a['price'] <=> $b['price'];
                    });

                    $upcItem['offers'] = array_map(function ($offer) {
                        $offer['price'] *= 100;
                        $offer['price'] = round($offer['price']);
                        return $offer;
                    }, $upcItem['offers']);
                }

                return response()->success([
                    'upc_item' => $upcItem,
                    'listed_upc_items' => ResourcesItem::collection($items),
                ]);
            case 429:
                // TODO: E-mail devs. Rate limited
            case 502:
                return response()->success([
                    'listed_upc_items' => ResourcesItem::collection($items),
                ]);
        }

        return response()->error('Something went wrong trying to fetch item data.');
    }

    public function getItemHistory($itemId)
    {
        $itemHistories = ItemHistory::where('item_id', $itemId)->get();
        return response()->success(ItemHistoryResource::collection($itemHistories));
    }

    public function calculatePrice(CalculatePriceRequest $request, ItemPricing $itemPricing) 
    {
        return response()->success([
            'price' => $itemPricing->calculatePrice(
                $request->input('price'), 
                $request->input('classification_id'), 
                $request->input('condition_id'), 
                $request->input('discount_id'),
                $request->input('discount_amount')
            )
        ]);
    }

    public function calculatePriceForMultipleItems(Request $request, ItemPricing $itemPricing)
    {
        $items = $request->input('items');
        $pricesArr = array_map(function($i) use ($itemPricing) {
            return [
                'id' => $i['id'], 
                'price' => $itemPricing->calculatePrice(
                    $i['price'],
                    isset($i['classification_id']) ? $i['classification_id'] : null,
                    isset($i['condition_id']) ? $i['condition_id'] : null,
                    isset($i['discount_id']) ? $i['discount_id'] : null,
                    isset($i['discount_amount']) ? $i['discount_amount'] : null,
                    isset($i['discount_amount_type']) ? $i['discount_amount_type'] : null,
                    isset($i['quantity_ordered']) ? $i['quantity_ordered'] : null
                )
            ];
        }, $items);

        return response()->success(['item_prices' => $pricesArr]);
    }

    public function calculateConsignmentFee(Request $request, ItemPricing $itemPricing)
    {
        $request->validate([
            'consignor_id' => 'required|integer|exists:consignors,id',
            'price' => 'required|integer|min:0'
        ]);

        return response()->success(
            $itemPricing->calculateConsignmentFee(
                Money::USD($request->price),
                $request->consignor_id
            )
        );
    }

    /** Creates generic JSON response for Item that does not exist. */
    private function itemDoesNotExistResponse()
    {
        return response()->error('Item does not exist for this User.');
    }
}
