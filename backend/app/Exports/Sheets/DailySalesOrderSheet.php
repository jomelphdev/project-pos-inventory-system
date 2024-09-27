<?php

namespace App\Exports\Sheets;

use App\Exports\Traits\DailySalesSheet;
use App\Models\PosOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DailySalesOrderSheet implements FromQuery, WithTitle, WithMapping, WithHeadings, WithEvents
{
    use DailySalesSheet, RegistersEventListeners;

    public function query()
    {
        return PosOrder::with('createdBy')->reportForStore($this->storeId, $this->date);
    }

    public function map($order): array
    {
        $cash = $order->cash - $order->change;

        return [
            $order->created_at->format('m/d/Y'),
            $order->id,
            $cash / 100,
            $order->card / 100,
            $order->ebt / 100,
            $order->sub_total / 100,
            $order->tax / 100,
            $order->total / 100,
            $order->total / 100,
            NULL,
            $order->order_cost / 100,
            $order->quantity_ordered,
            $order->createdBy->full_name
        ];
    }

    public function title(): string
    {
        return 'Orders';
    }
}
