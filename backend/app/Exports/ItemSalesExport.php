<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ItemSalesExport implements FromQuery, WithMapping, WithHeadings, WithTitle, WithColumnFormatting
{
    use Exportable;

    private $orgId;
    private $dateRange;

    public function __construct(int $orgId, Carbon $start, Carbon $end=null)
    {
        $this->orgId = $orgId;
        $this->dateRange = getDateRangeForReports(null, $start, $end); 
    }

    public function query()
    {
        return DB::table('pos_order_items', 'pi')
            ->select(
                'i.title', 
                'i.sku', 
                'i.upc', 
                'i.original_price', 
                'i.price', 
                'i.cost', 
                DB::raw('
                    AVG(pi.price) as avg_sale, 
                    SUM(pi.quantity_ordered) as quantity_sold, 
                    SUM(ri.quantity_returned) as quantity_returned'
                )
            )
            ->addSelect([
                'classification_name' => \App\Models\Classification::withTrashed()->select('name')->whereColumn('id', 'i.classification_id')->limit(1),
                'condition_name' => \App\Models\Condition::withTrashed()->select('name')->whereColumn('id', 'i.condition_id')->limit(1)
            ])
            ->leftJoin('items as i', 'i.id', '=', 'pi.item_id')
            ->leftJoin('pos_return_items as ri', 'ri.pos_order_item_id', '=', 'pi.id')
            ->whereNotNull('pi.item_id')
            ->whereBetween('pi.created_at', array_values($this->dateRange))
            ->where('organization_id', $this->orgId)
            ->groupBy(
                'pi.id',
                'i.classification_id',
                'i.condition_id',
                'i.title', 
                'i.sku', 
                'i.upc', 
                'i.original_price', 
                'i.price', 
                'i.cost'
            )
            ->orderBy('pi.id');
    }

    public function map($item): array
    {
        $soldAvg = intval($item->avg_sale) / 100;
        $cost = $item->cost / 100;
        $qtySold = $item->quantity_sold;
        $qtyReturned = $item->quantity_returned;
        $qtyDifference = $qtySold - $qtyReturned;

        return [
            $item->title,
            isset($item->condition_name) 
                ? $item->condition_name
                : '',
            isset($item->classification_name) 
                ? $item->classification_name
                : '',
            $item->sku,
            $item->upc,
            $item->original_price / 100,
            $item->price / 100,
            $soldAvg,
            ($cost * $qtyDifference),
            $qtySold,
            $qtyReturned,
            $qtyDifference,
            ($soldAvg * $qtyDifference)
        ];
    }

    public function headings(): array
    {
        return [
            'Item Title', 'Condition', 'Classification', 'SKU', 
            'UPC', 'Original Price', 'Retail Price', 'Avg. Sale Price',
            'Cost', 'Sold', 'Returned', 'Sales Difference', 'Total Sales'
        ];
    }

    public function title(): string
    {
        return 'Item Sales';
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_CURRENCY_USD_INTEGER,
            'G' => NumberFormat::FORMAT_CURRENCY_USD_INTEGER,
            'H' => NumberFormat::FORMAT_CURRENCY_USD_INTEGER,
            'I' => NumberFormat::FORMAT_CURRENCY_USD_INTEGER,
            'M' => NumberFormat::FORMAT_CURRENCY_USD_INTEGER
        ];
    }
}
