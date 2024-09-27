<?php

namespace App\CustomClass\Reports;

use App\Services\ReportService;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Sales
{

  private $spreadsheet;
  private $sheet;
  private $fileHeaders = [
    'Date', 'Cash', 'Charge', 
    'EBT', 'Sub-Total', 'Tax',  
    'Total', 'Store Total', 'Online Total', 
    'Non-Taxed Total', 'Cost', 'Sales', 
    'Returns'
  ];

  public function __construct($storeId, $storeName, Carbon $startDate, Carbon $endDate)
  {
    $this->data = ReportService::salesDataFor([$storeId], $startDate, $endDate);
    $this->storeName = $storeName;

    $this->sales = $this->data['sales'];
  }

  public function generateReport()
  {
    $this->spreadsheet = new Spreadsheet();
    $this->sheet = new Worksheet($this->spreadsheet, $this->storeName);
    $fileData = array_merge(
      [$this->fileHeaders], 
      $this->getBody(),
      [[]],
      [$this->fileHeaders]
    );
    
    addDataToBottomOfSheet($this->sheet, $fileData);
    salesFooterRows($this->sheet);

    $this->spreadsheet->addSheet($this->sheet);
    $this->spreadsheet->removeSheetByIndex(0);
    salesStyle($this->sheet);
    
    return $this->spreadsheet;
  }

  private function getBody()
  {
    $rows = [];
    foreach ($this->sales as $date => $totals)
    {
      $rowData = [
        $date,
        $totals['cash'] / 100,
        $totals['card'] / 100,
        $totals['ebt'] / 100,
        $totals['sub_total'] / 100,
        $totals['tax'] / 100,
        $totals['total'] / 100,
        $totals['total'] / 100,
        NULL,
        $totals['non_taxed_sub_total'] / 100,
        $totals['cost'] / 100,
        isset($totals['sales']) ? $totals['sales'] : 0,
        isset($totals['returns']) ? $totals['returns'] : 0
      ];

      array_push($rows, $rowData);
    }
    return $rows;
  }
}
?>