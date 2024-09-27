<?php

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

function removeCell($sheet, $cell)
{
  $sheet->setCellValue($cell, '');
}

function addDataToBottomOfSheet($sheet, $data)
{
  $row = $sheet->getHighestDataRow() + 1;
  $sheet->fromArray(
    $data,
    NULL,
    'A' . $row,
    true
  );
}

function dailySalesFooter(Worksheet $sheet)
{
  $highestRow = $sheet->getHighestDataRow();
        
  if ($highestRow == 1) 
  {
    $sheet->removeRow(1);
    return;
  }

  $sumRow = ($highestRow == 1) ? 2 : $highestRow;

  addDataToBottomOfSheet($sheet, [
      [],
      [
        'TOTALS',
        '=SUM(B2:B' . ($sumRow) . ')',
        '=SUM(C2:C' . ($sumRow) . ')',
        '=SUM(D2:D' . ($sumRow) . ')',
        '=SUM(E2:E' . ($sumRow) . ')',
        '=SUM(F2:F' . ($sumRow) . ')',
        '=SUM(G2:G' . ($sumRow) . ')',
        '=SUM(H2:H' . ($sumRow) . ')',
        NULL,
        '=SUM(J2:J' . ($sumRow) . ')',
        '=SUM(K2:K' . ($sumRow) . ')',
      ]
  ]);
}

function salesFooterRows($sheet)
{
  $highestRow = $sheet->getHighestDataRow();
        
  if ($highestRow == 1) 
  {
    $sheet->removeRow(1);
    return;
  }

  $sumRow = ($highestRow == 1) ? 2 : $highestRow;
  
  addDataToBottomOfSheet($sheet, [
    [],
    [
      'TOTALS',
      '=SUM(B2:B' . ($sumRow) . ')',
      '=SUM(C2:C' . ($sumRow) . ')',
      '=SUM(D2:D' . ($sumRow) . ')',
      '=SUM(E2:E' . ($sumRow) . ')',
      '=SUM(F2:F' . ($sumRow) . ')',
      '=SUM(G2:G' . ($sumRow) . ')',
      '=SUM(H2:H' . ($sumRow) . ')',
      NULL,
      '=SUM(J2:J' . ($sumRow) . ')',
      '=SUM(K2:K' . ($sumRow) . ')',
      '=SUM(L2:L' . ($sumRow) . ')',
      '=SUM(M2:M' . ($sumRow) . ')',
    ]
  ]);
}

function classificationSalesFooterRows($sheet)
{
  $row = $sheet->getHighestDataRow() - 2;
  addDataToBottomOfSheet($sheet, [
    [
      'TOTALS',
      '=SUM(B2:B' . $row . ')',
      '=SUM(C2:C' . $row . ')'
    ]
  ]);
}

function setClassificationSalesAsPercentage($sheet, $sheetData)
{
  // Re-run this loop so that we can get percentage sales of total for each classification
  foreach ($sheetData as $classificationName => $data)
  {
    $rowNumber = array_search($classificationName, $sheetData) + 2;
    $rowFormula = '=C' . $rowNumber . '/C' . $sheet->getHighestDataRow('C');
    $sheet->setCellValue('D' . $rowNumber, $rowFormula);
  }
}

// STYLE FUNCTIONS

function salesStyle($sheet) 
{
  $highestRow = $sheet->getHighestDataRow();

  removeCell($sheet, 'A' . ($highestRow - 2));
  boldHeaders($sheet);
  boldColumn($sheet, 'A');
  // Sets the final row to bold
  boldRow($sheet, $highestRow);
  // Sets dollar format for most of the sheet
  dollarFormat($sheet, 'B2:K' . $highestRow);
  // Sets number formats for last row
  percentageFormat($sheet, 'B' . $highestRow . ':D' . $highestRow);
  dollarFormat($sheet, 'K' . $highestRow);
}

function classificationSalesStyle($sheet)
{
  $highestRow = $sheet->getHighestDataRow();
    $summaryRows = $highestRow - 1;

    removeCell($sheet, 'A' . $summaryRows);
    removeCell($sheet, 'D' . $summaryRows);
    // Styles
    boldRow($sheet, '1');
    boldRow($sheet, $summaryRows);
    boldColumn($sheet, 'A');
    dollarFormat($sheet, 'C2:C' . $highestRow);
    percentageFormat($sheet, 'D2:D' . $highestRow);
}

function boldHeaders($sheet)
{
  $highestColumn = $sheet->getHighestColumn();
  $sumUpHeaders = $sheet->getHighestDataRow('A') - 2;
  $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
  $sheet->getStyle('A'. $sumUpHeaders . ':' . $highestColumn . $sumUpHeaders)->getFont()->setBold(true);
}

function boldColumn($sheet, $column)
{
  $highestRow = $sheet->getHighestDataRow();
  $sheet->getStyle($column . '1:' . $column . $highestRow)->getFont()->setBold(true);
}

function boldRow($sheet, $row)
{
  $highestColumn = $sheet->getHighestColumn();
  $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->getFont()->setBold(true);
}

function dollarFormat($sheet, $range)
{
  $sheet->getStyle($range)->getNumberFormat()
    ->setFormatCode(\PHPOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
}

function percentageFormat($sheet, $range)
{
  $sheet->getStyle($range)->getNumberFormat()
    ->setFormatCode(\PHPOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00);
}

?>