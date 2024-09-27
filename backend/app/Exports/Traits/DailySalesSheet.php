<?php

namespace App\Exports\Traits;

use Carbon\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;

trait DailySalesSheet
{
    private $storeId;
    private $date;

    public function __construct(int $storeId, Carbon $date)
    {
        $this->storeId = $storeId;
        $this->date = $date;
    }

    public function headings(): array
    {
        return [
            'Date', 'Order #', 'Cash', 'Charge',
            'EBT', 'Sub-Total', 'Tax', 
            'Total', 'In-Store Total', 'Online Total', 
            'Cost', '# of Items', "Employee"
        ];
    }

    public static function afterSheet(AfterSheet $event)
    {
        $sheet = $event->sheet->getDelegate();
        dailySalesFooter($sheet);
    }
}

?>