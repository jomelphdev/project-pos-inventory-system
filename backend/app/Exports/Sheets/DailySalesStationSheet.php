<?php

namespace App\Exports\Sheets;

use App\Exports\Traits\DailySalesSheet;
use App\Models\CheckoutStation;
use App\Models\PosOrder;
use App\Models\PosReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DailySalesStationSheet implements FromCollection, WithTitle, WithMapping, WithHeadings, WithEvents
{
    use DailySalesSheet, RegistersEventListeners;

    private $storeId;
    private $station;
    private $date;

    public function __construct(int $storeId, CheckoutStation $station, Carbon $date)
    {
        $this->storeId = $storeId;
        $this->station = $station;
        $this->date = $date;
    }

    public function collection()
    {
        $orders = PosOrder::with('createdBy')->reportForStore($this->storeId, $this->date)->where('checkout_station_id', $this->station->id)->get();
        $returns = PosReturn::with('createdBy')->reportForStore($this->storeId, $this->date)->where('checkout_station_id', $this->station->id)->get();

        return $orders->merge($returns);
    }

    public function map($row): array
    {
        // IF PosReturn
        if ($row->pos_order_id)
        {
            return [
                $row->created_at->format('m/d/Y'),
                'R ' . $row->pos_order_id,
                -$row->cash / 100,
                -$row->card / 100,
                -$row->ebt / 100,
                -$row->sub_total / 100,
                -$row->tax / 100,
                -$row->total / 100,
                -$row->total / 100,
                NULL,
                -$row->return_cost / 100,
                -$row->quantity_returned,
                $row->createdBy->full_name
            ];
        }

        return [
            $row->created_at->format('m/d/Y'),
            $row->id,
            ($row->cash - $row->change) / 100,
            $row->card / 100,
            $row->ebt / 100,
            $row->sub_total / 100,
            $row->tax / 100,
            $row->total / 100,
            $row->total / 100,
            NULL,
            $row->order_cost / 100,
            $row->quantity_ordered,
            $row->createdBy->full_name
        ];
    }

    public function title(): string
    {
        return $this->station->store->name.' -- '.$this->station->name;
    }
}