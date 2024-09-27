<?php

namespace App\Exports;

use App\Exports\Sheets\InventoryForStoreSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InventoryExport implements WithMultipleSheets
{
    use Exportable;

    protected $stores;
    protected $userId;
    private $withEmptyQuantities;

    public function __construct(array $stores, int $userId, bool $withEmptyQuantities=false)
    {
        $this->stores = $stores;
        $this->userId = $userId;
        $this->withEmptyQuantities = $withEmptyQuantities;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->stores as $storeId)
        {
            $sheets[] = new InventoryForStoreSheet($storeId, $this->withEmptyQuantities);
        }

        return $sheets;
    }
}
