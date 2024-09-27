<?php

namespace App\Exports;

use App\Exports\Sheets\SalesForStoreSheet;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;

class SalesExport implements WithMultipleSheets, WithPreCalculateFormulas
{
    use Exportable;
    
    private $stores;
    private $startDate;
    private $endDate;

    public function __construct(array $stores, Carbon $startDate, Carbon $endDate)
    {
        $this->stores = $stores;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->stores as $id)
        {
            $sheets[] = new  SalesForStoreSheet($id, $this->startDate, $this->endDate);
        }

        return $sheets;
    }
}
