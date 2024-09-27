<?php

namespace App\Http\Controllers;

use App\Http\Requests\PosReturnStoreRequest;
use App\Http\Resources\PosReturn as PosReturnResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PosReturn;
use App\Services\ItemService;
use App\Services\PosReturnService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PosReturnController extends Controller
{
    /**
     * @param \App\Http\Requests\PosReturnStoreRequest $request
     * @return \App\Http\Resources\PosReturn
     */
    public function store(PosReturnStoreRequest $request, PosReturnService $posReturnService, ItemService $itemService)
    {
        $data = $request->validated();
        $data['organization_id'] = $request->user()->organization_id;
        $this->authorize('create', PosReturn::class);

        try
        {
            $return = $posReturnService->createReturn($data, $request);
        }
        catch (Exception $e)
        {
            Log::channel('single')->error($e);
            if ($e->getCode() == 100)
            {
                return response()->error($e->getMessage());
            }
            
            return response()->error("Something went wrong while trying to create return.");
        }
        
        return response()->success(['return' => new PosReturnResource($return)]);
    }

    public function calculateRefund(Request $request) {
        $orderId = $request->input('pos_order_id');
        $items = $request->input('items');

        $refundMath = PosReturnService::calculateRefund($orderId, $items);

        return response()->success($refundMath);
    }
}
