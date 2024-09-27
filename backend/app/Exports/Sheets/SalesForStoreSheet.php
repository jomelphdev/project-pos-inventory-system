<?php

namespace App\Exports\Sheets;

use App\Models\Store;
use App\Services\ReportService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class SalesForStoreSheet implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents
{
    use RegistersEventListeners;

    private $storeId;
    private $store;
    private $startDate;
    private $endDate;

    public function __construct(int $storeId, Carbon $startDate, Carbon $endDate)
    {
        $this->storeId = $storeId;
        $this->store = Store::find($storeId);
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return ReportService::salesDataFor([$this->storeId], $this->startDate, $this->endDate);
    }

    public function map($row): array
    {
        return [
            $row['date'],
            $row['cash'] / 100,
            $row['card'] / 100,
            $row['ebt'] / 100,
            $row['sub_total'] / 100,
            $row['tax'] / 100,
            $row['total'] / 100,
            $row['total'] / 100,
            NULL,
            $row['non_taxed_sub_total'] / 100,
            $row['cost'] / 100,
            isset($row['sales']) ? $row['sales'] : 0,
            isset($row['returns']) ? $row['returns'] : 0
        ];
    }

    public function headings(): array
    {
        return [
            'Date', 'Cash', 'Charge', 
            'EBT', 'Sub-Total', 'Tax',  
            'Total', 'Store Total', 'Online Total', 
            'Non-Taxed Total', 'Cost', 'Sales', 
            'Returns'
        ];
    }

    public function title(): string
    {
        return $this->store->name;
    }

    public function afterSheet(AfterSheet $event)
    {
        $sheet = $event->sheet->getDelegate();
        salesFooterRows($sheet);
    }
}
