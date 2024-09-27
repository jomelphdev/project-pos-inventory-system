<?php

namespace App\CustomClass\Reports;

use App\Models\Store;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassificationSales
{

  private $spreadsheet;
  private $sheet;
  private $store;
  private $orders;
  private $returns;
  private $salesData;
  private $fileHeaders = [
    'Classification', 'Items Sold', 'Revenue', '% of Total'
  ];

  public function __construct(Store $store, array $orders, array $returns)
  {
    $this->store = $store;
    $this->orders = $orders;
    $this->returns = $returns;
  }

  public function generateReport()
  {
    $this->spreadsheet = new Spreadsheet();
    $this->sheet = new Worksheet($this->spreadsheet, $this->store['name']);
    $fileData = array_merge(
      [$this->fileHeaders], 
      $this->getBody(),
      [[]],
      [$this->fileHeaders]
    );
    
    addDataToBottomOfSheet($this->sheet, $fileData);
    classificationSalesFooterRows($this->sheet);
    setClassificationSalesAsPercentage($this->sheet, $this->salesData);

    $this->spreadsheet->addSheet($this->sheet);
    $this->spreadsheet->removeSheetByIndex(0);
    classificationSalesStyle($this->sheet);
    
    return $this->spreadsheet;
  }

  private function ordersMap()
  {
    foreach ($this->orders as $order)
    {
      foreach ($order->posOrderItems as $item)
      {
        $classification = $item->item->classification->name;
        $quantityOrdered = $item->quantity_ordered;
        if (!isset($this->salesData[$classification]))
        {
          $this->salesData[$classification] = [
            'itemsSold' => 0,
            'revenue' => 0
          ];
        }
        
        $this->salesData[$classification]['itemsSold'] += $quantityOrdered;
        $this->salesData[$classification]['revenue'] += $item->price * $quantityOrdered;
      }
    }
  }

  private function getBody()
  {
    $this->ordersMap();

    $rows = [];
    foreach ($this->salesData as $classificationName => $data)
    {
      $rowData = [
        $classificationName,
        $data['itemsSold'],
        $data['revenue']
      ];

      array_push($rows, $rowData);
    }
    return $rows;
  }
}
?>