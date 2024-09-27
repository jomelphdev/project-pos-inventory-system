<?php

namespace App\Exports\Sheets;

use App\Exports\Traits\DailySalesSheet;
use App\Models\PosReturn;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DailySalesReturnSheet implements FromQuery, WithTitle, WithMapping, WithHeadings, WithEvents
{
    use DailySalesSheet, RegistersEventListeners;

    public function query()
    {
        return PosReturn::with('createdBy')->reportForStore($this->storeId, $this->date);
    }

    public function map($return): array
    {
        return [
            $return->created_at->format('m/d/Y'),
            'R ' . $return->pos_order_id,
            $return->cash / 100,
            $return->card / 100,
            $return->ebt / 100,
            $return->sub_total / 100,
            $return->tax / 100,
            $return->total / 100,
            $return->total / 100,
            NULL,
            $return->return_cost / 100,
            $return->quantity_returned,
            $return->createdBy->full_name
        ];
    }

    public function title(): string
    {
        return 'Returns';
    }
}
