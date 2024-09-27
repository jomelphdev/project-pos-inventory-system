<?php

namespace App\Http\Controllers;

use App\Http\Requests\PosOrderStoreRequest;
use App\Http\Resources\PosOrder as PosOrderResource;
use App\Models\PosOrder;
use App\Services\PosOrderService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Money\Money;

class PosOrderController extends Controller
{
    public function index($userId)
    {
        $posOrders = PosOrder::all()->where('user_id', $userId);

        return response()->json([
            'success' => true,
            'orders' => PosOrderResource::collection($posOrders)
        ]);
    }

    public function store(PosOrderStoreRequest $request, PosOrderService $posOrderService)
    {
        $newOrder = $request->validated();
        $newOrder['organization_id'] = $request->user()->organization_id;

        $this->authorize('create', PosOrder::class);

        try
        {
            $newOrder = $posOrderService->createOrder($newOrder, $request);
        }
        catch (Exception $e)
        {
            if ($e->getCode() == 100)
            {
                return response()->error($e->getMessage());
            }
            
            Log::error($e);
            return response()->error("Something went wrong while trying to create order.");
        }
        
        return response()->success(['order' => new PosOrderResource($newOrder)]);
    }

    public function show(Request $request, PosOrderService $posOrderService, $id)
    {
        try
        {
            $posOrder = $posOrderService->getOrder($id);
            $this->authorize('view', $posOrder);
        }
        catch (ModelNotFoundException $e)
        {
            return response()->error('Order does not exist.');
        }
        
        return response()->success(['order' => new PosOrderResource($posOrder)]);
    }

    public function getOrderForReturn(Request $request, PosOrderService $posOrderService, $id)
    {
        try
        {
            $order = $posOrderService->getOrderForReturnById($id);
            $this->authorize('view', $order);
        }
        catch(ModelNotFoundException $e)
        {
            return response()->error('Order does not exist.');
        }

        return response()->success(['order' => new PosOrderResource($order)]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Integer $id
     * @return \App\Http\Resources\PosOrder
     */
    public function getOrdersForOrganization(Request $request)
    {
        $request->validate([
            'last_seen_id' => 'nullable|int',
            'query' => 'nullable|string',
            'date_to' => 'nullable|date',
            'date_from' => 'nullable|date'
        ]);
        $orgId = $request->user()->organization_id;
        $itemQuery = $request->input('query');

        $this->authorize('viewAny', PosOrder::class);

        try
        {
            $ordersQuery = PosOrder::query()
                ->without('posOrderItems')
                ->withQuantityOrdered()
                ->where('organization_id', $orgId)
                ->orderBy('created_at', 'DESC')
                ->limit(30);
            
            if ($request->last_seen_id)
            {
                $ordersQuery = $ordersQuery->where('id', '<', $request->last_seen_id);
            }
            if ($itemQuery)
            {
                $ordersQuery = $ordersQuery->whereHas('posOrderItems.item', function (Builder $q) use ($itemQuery) {
                    return $q->where('upc', $itemQuery)->orWhere('sku', $itemQuery);
                });
            }
            if ($request->date_from)
            {
                $ordersQuery = $ordersQuery->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->date_to)
            {
                $ordersQuery = $ordersQuery->whereDate('created_at', '<=', $request->date_to);
            }

            $orders = $ordersQuery->get();
        }
        catch (ModelNotFoundException $e)
        {
            return response()->error($e->getMessage());
        }
        
        return response()->success([
            'orders' => PosOrderResource::collection($orders),
        ]);
    }

    public function calculateOrderTotals(Request $request)
    {
        $totals = PosOrderService::calculateTotals(
            $request->input('store_id'),
            collect($request->input('items')),
            $request->input('is_taxed'),
            $request->input('ebt_order'),
            $request->input('discount_amount', 0)
        );

        return response()->success($totals);
    }

    public function calculatePayment(Request $request)
    {
        $zero = Money::USD(0);
        $cash = Money::USD($request->input('cash', 0));
        $card = Money::USD($request->input('card', 0));
        $ebt = Money::USD($request->input('ebt', 0));
        $gc = Money::USD($request->input('gc', 0));
        $total = Money::USD($request->input('total', 0));

        $paidSum = $zero->add($cash, $card, $ebt, $gc);
        $amountRemaining = $total->subtract($paidSum);
        $change = $amountRemaining->lessThanOrEqual($zero) ? $amountRemaining->absolute() : $zero;

        return response()->success([
            'amount_remaining' => $amountRemaining->isNegative() ? 0 : (int) $amountRemaining->getAmount(),
            'amount_paid' => (int) $paidSum->getAmount(),
            'change' => (int) $change->getAmount()
        ]);
    } 

    public function promotionalLogs($itemId) {
        $posOrders = PosOrder::with('posOrderItems')
        ->whereHas('posOrderItems', function ($query) use ($itemId) {
            $query->where('item_id', $itemId)
            ->whereNotNull('item_specific_discount_id');
        })
        ->get();

        return response()->success($posOrders);
    }
}
