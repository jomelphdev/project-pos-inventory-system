<?php

namespace App\Exports;

use App\Exports\Sheets\DailySalesOrderSheet;
use App\Exports\Sheets\DailySalesReturnSheet;
use App\Exports\Sheets\DailySalesStationSheet;
use App\Models\Store;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithPreCalculateFormulas;

class DailySalesExport implements WithMultipleSheets, WithPreCalculateFormulas
{
    use Exportable;

    private $storeId;
    private $store;
    private $date;
    private $forStations;

    public function __construct(int $storeId, Carbon $date, bool $forStations=false)
    {
        $this->storeId = $storeId;
        $this->store = Store::find($storeId);
        $this->date = $date;
        $this->forStations = $forStations;
    }

    public function sheets(): array
    {
        $sheets = [];

        if (count($this->store->checkoutStations) > 0 && $this->forStations)
        {
            foreach ($this->store->checkoutStations as $station)
            {
                $sheets[] = new DailySalesStationSheet($this->storeId, $station, $this->date);
            }
        }
        else 
        {
            $sheets = [
                new DailySalesOrderSheet($this->storeId, $this->date),
                new DailySalesReturnSheet($this->storeId, $this->date)
            ];
        }
        
        return $sheets; 
    }
}
