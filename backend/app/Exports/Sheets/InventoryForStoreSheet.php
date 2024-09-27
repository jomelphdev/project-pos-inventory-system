<?php

namespace App\Exports\Sheets;

use App\Models\Item;
use App\Models\Store;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class InventoryForStoreSheet implements FromQuery, WithTitle, WithHeadings, WithMapping
{
    private $storeId;
    private $store;
    private $withEmptyQuantities;

    public function __construct(int $storeId, bool $withEmptyQuantities=false)
    {
        $this->storeId = $storeId;
        $this->store = Store::find($storeId);
        $this->withEmptyQuantities = $withEmptyQuantities;
    }

    public function query()
    {
        return Item::inventoryForStores([$this->storeId], $this->store->organization_id, $this->withEmptyQuantities);
    }

    public function map($item): array
    {
        return [
            $item->title,
            $item->description,
            isset($item->condition_name) 
                ? $item->condition_name
                : '',
            isset($item->classification_name) 
                ? $item->classification_name
                : '',
            $item->sku,
            $item->upc,
            $item->images->implode(','),
            $item->original_price / 100,
            $item->price / 100,
            $item->cost / 100,
            $item->quantity,
            $item->length['value'],
            $item->width['value'],
            $item->depth['value'],
            $item->weight['value'],
        ];
    }

    public function headings(): array
    {
        return [
            'Item Title', 'Description', 'Condition', 'Classification',
            'SKU', 'UPC', 'Images', 'Original Price', 'Retail Price', 
            'Cost', 'Total Quantity', 'Length (in)', 'Width (in)', 
            'Depth (in)', 'Weight (oz)'
        ];
    }

    public function title(): string
    {
        return $this->store->name;
    }
}
