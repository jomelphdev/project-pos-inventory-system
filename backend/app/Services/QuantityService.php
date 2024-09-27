<?php

namespace App\Services;

use App\Models\CurrentQuantity;
use Illuminate\Support\Facades\DB;

class QuantityService
{
    public function setCurrentQuantity(int $storeId, int $itemId, int $quantity=null)
    {
        $existingQuantity = CurrentQuantity::where([
            ['store_id', $storeId],
            ['item_id', $itemId]
        ])->first();

        if ($existingQuantity || !$quantity)
        {
            $quantity = $this->calculateCurrentQuantity($storeId, $itemId);
        }

        if ($existingQuantity)
        {
            if ($quantity != $existingQuantity->quantity)
            {
                $existingQuantity->quantity = $quantity;
                $existingQuantity->save();
            }

            return $existingQuantity;
        }

        $quantity = new CurrentQuantity([
            'store_id' => $storeId,
            'item_id' => $itemId,
            'quantity' => $quantity
        ]);
        $quantity->save();

        return $quantity;
    }

    public function calculateCurrentQuantity(int $storeId, int $itemId)
    {
        $sqlStatement = "
        SELECT
        (
            COALESCE((
                SELECT SUM(q.quantity_received)
                FROM quantities AS q
                WHERE q.item_id=? AND q.store_id=?
            ), 0) + 
            COALESCE((
                SELECT SUM(ri.quantity_returned)
                FROM pos_return_items AS ri
                LEFT JOIN pos_returns as pr ON ri.pos_return_id=pr.id
                WHERE ri.item_id=? AND pr.store_id=? AND action=1
            ), 0) -
            COALESCE((
                SELECT SUM(oi.quantity_ordered)
                FROM pos_order_items AS oi
                LEFT JOIN pos_orders AS po ON oi.pos_order_id=po.id
                WHERE oi.item_id=? AND po.store_id=?
            ), 0)
        ) AS quantity;
        ";
        $quantity = DB::select($sqlStatement, [$itemId, $storeId, $itemId, $storeId, $itemId, $storeId])[0]->quantity;

        return $quantity;
    }
}

?>