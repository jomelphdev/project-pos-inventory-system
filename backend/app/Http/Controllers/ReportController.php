<?php

namespace App\Http\Controllers;

use App\Events\LateReply;
use App\Http\Resources\ConsignorInvoiceResource;
use App\Http\Resources\GiftCardResource;
use App\Jobs\ProcessClassificationSalesFile;
use Illuminate\Http\Request;
use App\Jobs\ProcessDailySalesFile;
use App\Jobs\ProcessInventoryReport;
use App\Jobs\ProcessItemSalesFile;
use App\Jobs\ProcessSalesFile;
use App\Models\DrawerLog;
use App\Models\Item;
use App\Models\Report;
use App\Models\Store;
use App\Models\GiftCard;
use App\Services\PreferencesService;
use App\Services\ReportService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportController extends Controller
{
  private $headers = [
    'Access-Control-Allow-Origin' => '*',
    'Access-Control-Allow-Headers' => 'Content-Type, X-Auth-Token, Origin',
    'Access-Control-Allow-Methods' => 'POST, GET',
    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'Cache-Control' => 'max-age=0'
  ];

  public function getReportDirectories(Request $request)
  {
    $request->merge($request->query());
    $request->validate([
      'report_type' => 'required|string',
      'store_id' => 'nullable|integer'
    ]);

    $orgId = $request->user()->organization_id;
    $dir = 'reports/' . $orgId . '/' . $request->report_type;

    if ($request->store_id)
    {
      $dir .= '/' . $request->store_id;
    }

    $directories = array_map(fn($directory) => basename($directory), Storage::disk('s3')->directories($dir));
    $files = array_map(fn($file) => basename($file), Storage::disk('s3')->files($dir));

    if (count($files) > 0) 
    {
      $files = Report::where('organization_id', $orgId)
        ->where('report_type', $request->report_type)
        ->where('store_id', $request->store_id)
        ->whereIn('file_name', $files)
        ->orderBy('created_at', 'DESC')
        ->get();
    }

    return response()->success([
      'directories' => $directories,
      'files' => $files
    ]);
  }

  public function getDailySalesReport(Request $request) 
  {
    $data = $request->all();
    $store = Store::find($data['store_id']);
    $storeId = $store->id;
    $orgId = $request->user()->organization_id;
    $date = new Carbon($data['date']);
    $dateRange = getDateRangeForReports($storeId, $date);

    if (Cache::has('processing:daily-sales:' . $storeId))
    {
      return response()->json([
        'success' => false,
        'message' => 'Already have a daily sales report processing for this store.'
      ]);
    }

    $report = Report::where('organization_id', $orgId)
      ->where('store_id', $storeId)
      ->where('report_type', 'daily_sales')
      ->whereDate('from_date', $dateRange['start_date']->toDateString())
      ->first();

    if ($report && Storage::disk('s3')->exists($report->file_path))
    {
      $file = Storage::disk('s3')->get($report->file_path);
      return response($file);
    } else if ($report)
    {
      $report->delete();
    }

    ProcessDailySalesFile::dispatch($storeId, $dateRange['start_date'], $request->user()->id, $request->input('for_stations', false));

    return response()->json([
      'success' => true,
      'message' => 'Your report is processsing we will notify you when it is done.'
    ], 200);
  }

  public function getDailySalesReportData(Request $request, ReportService $reportService) {
    $data = $request->all();
    $date = new Carbon($data['date']);
    
    return response()->success($reportService->dailySalesDataFor($data['store_id'], $date));
  }

  public function getSalesReport(Request $request) {
    $data = $request->all();
    $startDate = new Carbon($data['start_date']);
    $endDate = new Carbon($data['end_date']);
    $user = $request->user();
    $orgId = $user->organization_id;
    $userId = $user->id;
    $fileName = $startDate->copy()->format('m-d-Y') . '_' . $endDate->copy()->format('m-d-Y') . '_Sales.xlsx';
    $storeIds = $data['stores'];

    $masterSpreadsheet = new Spreadsheet();
    $missingStores = [];
    foreach ($storeIds as $id)
    {
      $dateRange = getDateRangeForReports($id, $startDate, $endDate);
      $report = Report::where('organization_id', $orgId)
        ->where('store_id', $id)
        ->where('report_type', 'sales')
        ->whereDate('from_date', $dateRange['start_date'])
        ->whereDate('to_date', $dateRange['end_date'])
        ->first();
      
      if ($report && Storage::disk('s3')->exists($report->file_path))
      {
        $contents = Storage::disk('s3')->get($report->file_path);
        $spreadsheet = $this->extractSpreadsheet($contents)->getActiveSheet(); 
        $masterSpreadsheet->addExternalSheet(clone $spreadsheet);
        continue;
      }

      array_push($missingStores, new ProcessSalesFile($id, $startDate->copy(), $endDate->copy()));
    }

    if (count($missingStores) == 0)
    {
      $masterSpreadsheet->removeSheetByIndex(0);
      $response = response()->streamDownload(function() use ($masterSpreadsheet) {
        $writer = new XlsxWriter($masterSpreadsheet);
        $writer->save('php://output');
      }, $fileName, $this->headers);

      return $response;
    }

    Bus::batch($missingStores)->then(function (Batch $batch) use ($userId) {
      LateReply::dispatch([
        'success' => true,
        'user_id' => $userId,
        'message' => 'Your report is ready for download!',
        'reportType' => 'sales',
        'response_type' => 'file-finished'
      ]);
    })->dispatch();

    return response()->json([
      'success' => true,
      'message' => 'Your report is processsing we will notify you when it is done.'
    ], 200);
  }

  public function getSalesReportData(Request $request, ReportService $reportService) {
    $data = $request->all();
    $startDate = new Carbon($data['start_date']);
    $endDate = new Carbon($data['end_date']);
    
    return response()->success($reportService->salesDataFor($data['stores'], $startDate, $endDate, true));
  }

  public function getItemSalesReport(Request $request)
  {
    $user = $request->user();
    $orgId = $user->organization_id;
    $request->merge($request->query());
    $data = $request->validate([
      'start_date' => 'required|date',
      'end_date' => 'nullable|date',
    ]);

    if (Cache::has('processing:item-sales:' . $orgId))
    {
      return response()->json([
        'success' => false,
        'message' => 'Already have a item sales report processing.'
      ]);
    }

    $dateRange = getDateRangeForReports(null, new Carbon($data['start_date']), new Carbon($data['end_date']));
    $report = Report::where('organization_id', $orgId)
      ->where('report_type', 'item-sales')
      ->whereDate('from_date', $dateRange['start_date'])
      ->whereDate('to_date', $dateRange['end_date'])
      ->first();

    if ($report && Storage::disk('s3')->exists($report->file_path))
    {
      $file = Storage::disk('s3')->get($report->file_path);
      return response($file);
    } else if ($report)
    {
      $report->delete();
    }

    ProcessItemSalesFile::dispatch($user, $dateRange['start_date'], $dateRange['end_date']);

    return response()->json([
      'success' => true,
      'message' => 'Your report is processsing we will notify you when it is done.'
    ]);
  }

  public function createConsignmentInvoice(Request $request, ReportService $reportService)
  {
    $request->validate(['consignor_id' => 'required|integer|exists:consignors,id']);
    $user = $request->user();
    $orgId = $user->organization_id;
    $preferenceId = $user->preferences->id;

    try
    {
      $invoice = $reportService->createConsignmentInvoice($orgId, $preferenceId, $request->consignor_id);
    }
    catch (Exception $e)
    {
      return response()->error('Something went wrong while trying to create invoice.');
    }

    return response()->success(['invoice' => $invoice]);
  }

  public function getConsignmentInvoices(Request $request, ReportService $reportService)
  {
    $orgId = $request->user()->organization_id;
    $page = $request->input('page', 1);

    $invoices = $reportService->getConsignmentInvoices($orgId, $page);

    return response()->success([
      'current_page' => $invoices->currentPage(),
      'invoices' => ConsignorInvoiceResource::collection($invoices->items()),
      'to' => $invoices->lastItem(),
      'total' => $invoices->total()
    ]);
  }

  public function getConsignmentReportData(Request $request, ReportService $reportService)
  {
    $user = $request->user();
    $orgId = $user->organization_id;
    $preferenceId = $user->preferences->id;

    return response()->success($reportService->getConsignmentReportData($orgId, $preferenceId));
  }

  public function getClassificationSalesReport(Request $request)
  {
    $data = $request->all();
    $userId = $request->user()->id;
    $startDate = new Carbon($data['startDate']);
    $endDate = new Carbon($data['endDate']);
    $fileName = $startDate->copy()->format('m-d-Y') . '_' . $endDate->copy()->format('m-d-Y') . '_ClassificationSales.xlsx';
    $storeIds = $data['stores'];
    $stores = Store::query()->whereIn('id', $storeIds)->get()->all();

    $masterSpreadsheet = new Spreadsheet();
    $missingStores = [];
    $totals = [];
    foreach ($stores as $store)
    {
      $filePath = 'reports/' . $store->user_id . '/classification-sales/' . $store->id . '/' . $fileName;
      
      if (Storage::disk('s3')->exists($filePath))
      {
        $contents = Storage::disk('s3')->get($filePath);
        $spreadsheet = $this->extractSpreadsheet($contents)->getActiveSheet();
        $sheetData = $spreadsheet->rangeToArray(
          'A2:C' . $spreadsheet->getHighestDataRow(),
          NULL,
          true,
          false,
          true
        );
        $totals = $this->parseForClassificationData($totals, $sheetData);
        $masterSpreadsheet->addExternalSheet(clone $spreadsheet);
        continue;
      }

      array_push($missingStores, new ProcessClassificationSalesFile($store, [$startDate->copy()->endOfDay(), $endDate->copy()->endOfDay()]));
    }
    
    if (count($missingStores) == 0)
    {
      $totalsWorksheet = $this->addClassificationTotalsWorksheet($masterSpreadsheet, $totals);
      $masterSpreadsheet->addSheet($totalsWorksheet);
      $masterSpreadsheet->removeSheetByIndex(0);
      classificationSalesStyle($totalsWorksheet);

      $response = response()->streamDownload(function() use ($masterSpreadsheet) {
        $writer = new XlsxWriter($masterSpreadsheet);
        $writer->save('php://output');
      }, $fileName, $this->headers);

      return $response;
    }

    Bus::batch($missingStores)
    ->then(function (Batch $batch) use ($userId, $storeIds, $startDate, $endDate) {
      event(new LateReply([
        'success' => true,
        'user_id' => $userId,
        'message' => 'Your report is ready for download!',
        'stores' => $storeIds,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'reportType' => 'sales',
        'response_type' => 'file-finished'
      ]));
    })
    ->dispatch();

    return response()->json([
      'success' => true,
      'message' => 'Your report is processsing we will notify you when it is done.'
    ], 200);
  }

  public function getInventoryReport(Request $request)
  {
    $data = $request->all();
    $user = $request->user();
    $orgId = $user->organization_id;
    $storeIds = $data['stores'];
    
    try
    {
      if (Cache::has('processing:inventory:' . $orgId))
      {
        return response()->json([
          'success' => false,
          'message' => 'Already have a inventory report processing.'
        ]);
      }

      $reportQuery = Report::
        where('organization_id', $orgId)
        ->where('report_type', 'inventory');
      $existingReport = $reportQuery->first();

      if ($existingReport && Storage::disk('s3')->exists($existingReport->file_path))
      {
        $contents = Storage::disk('s3')->get($existingReport->file_path);

        Storage::disk('s3')->delete($existingReport->file_path);
        $reportQuery->delete();

        return response($contents);
      } else if ($existingReport)
      {
        $reportQuery->delete();
      }
      
      ProcessInventoryReport::dispatch($user, $storeIds, $request->input('with_empty_quantities', false));
    }
    catch (Exception $e)
    {
      Log::error($e->getMessage());
      return response()->error('Something went wrong while trying to generate report.');
    }

    return response()->json([
      'success' => true,
      'message' => 'Your report is processsing we will notify you when it is done.'
    ]);
  }

  public function getInventoryReportData(Request $request)
  {
    $data = $request->all();
    $stores = $data['stores'];

    return response()->success(Item::inventoryForStores($stores)->get());
  }

  public function getCashDrawersReport(Request $request, ReportService $reportService)
  {
    $id = $request->user()->preferences->id;

    try
    {
      $drawerReport = $reportService->getCashDrawersReport($id);
    }
    catch (Exception $e)
    {
      return response()->error('Something went wrong while trying to calculate cash drawers report.');
    }

    return response()->success($drawerReport);
  }

  public function setNewDrawerBalance(Request $request, ReportService $reportService, PreferencesService $preferencesService)
  {
    $user = $request->user();
    $request->merge([
      'organization_id' => $user->organization_id,
    ]);

    $data = $request->validate([
      'organization_id' => 'required|integer|exists:organizations,id',
      'checkout_station_id' => 'required|integer|exists:checkout_stations,id',
      'actual_difference' => 'required|integer',
      'new_balance' => 'required|integer'
    ]);

    try
    {
      $drawerData = $reportService->getReportForDrawer($data['checkout_station_id']);
      $data['started_at'] = $drawerData['last_balanced'];
      $data['expected_difference'] = $drawerData['difference'];
      
      DB::beginTransaction();

      $log = new DrawerLog($data);
      $log->save();
      $preferencesService->updateOrCreatePreferences($user->preferences->id, [
        'type' => 'checkout_stations',
        'update' => [
          'id' => $data['checkout_station_id'],
          'drawer_balance' => $data['new_balance'],
          'last_balanced' => $log->created_at
        ]
      ]);

      DB::commit();
    }
    catch (Exception $e)
    {
      DB::rollBack();
      return response()->error('Something went wrong while trying to set new balance.');
    }

    return response()->success();
  }

  public function download(Request $request)
  {
    $request->merge($request->query());
    $request->validate([
      'path' => 'required|string'
    ]);

    try
    {
      return response(Storage::disk('s3')->get($request->path));
    }
    catch (Exception $e)
    {
      return response()->error('Something went wrong while trying to download report.');
    }
  }

  public function regenerateReport(Request $request, $id)
  {
    $report = Report::findOrFail($id);
    $user = $request->user();

    if ($report->organization_id !== $user->organization_id)
    {
      return response()->error('You do not have permission to regenerate this report.');
    }

    try
    {
      $report->delete();
      switch ($report->report_type)
      {
        case 'sales':
          ProcessSalesFile::dispatch($report->store_id, $report->from_date, $report->to_date, $user->id);
          break;
        case 'daily_sales':
          ProcessDailySalesFile::dispatch($report->store_id, $report->to_date, $user->id);
          break;
        case 'item-sales':
          ProcessItemSalesFile::dispatch($user, $report->from_date, $report->to_date);
          break;
      }
    }
    catch (Exception $e)
    {
      return response()->error('Something went wrong while trying to regenerate report.');
    }

    return response()->json([
      'success' => true,
      'message' => 'Your report is processsing we will notify you when it is done.'
    ], 200);
  }

  public function delete(Request $request, $id)
  {
    try
    {
      $report = Report::findOrFail($id);
      $report->delete();
    }
    catch (Exception $e)
    {
      return response()->error('Something went wrong while trying to delete report.');
    }
  }

  private function extractSpreadsheet($contents)
  {
    $randFilename = uniqid(rand(), true) . '.xlsx';
    $tempFile = tempnam('/tmp', $randFilename);
    $handle = fopen($tempFile, 'w');
    fwrite($handle, $contents);
    fclose($handle);

    $reader = new Xlsx();
    $spreadsheet = $reader->load($tempFile);
    return $spreadsheet;
  }

  private function parseForClassificationData($totals, $sheetData)
  {
    foreach ($sheetData as $i => $row)
    {
      $classificationName = $row['A'];
      if ($classificationName != null && $classificationName != 'TOTALS')
      {
        if (!isset($totals[$classificationName]))
        {
          $totals[$classificationName]['name'] = $classificationName;
          $totals[$classificationName]['itemsSold'] = 0;
          $totals[$classificationName]['revenue'] = 0;
        }

        $totals[$classificationName]['itemsSold'] += $row['B'];
        $totals[$classificationName]['revenue'] += $row['C'];
      }
    }

    return $totals;
  }

  private function addClassificationTotalsWorksheet(Spreadsheet $masterSpreadsheet, $totals)
  {
    $totalsWorksheet = new Worksheet($masterSpreadsheet, 'TOTALS');
    $sheetData = array_merge(
      [['Classification', 'Items Sold', 'Revenue', '% of Sales']],
      $totals,
      [[], ['Classification', 'Items Sold', 'Revenue', '% of Sales']]
    );
    $totalsWorksheet->fromArray(
      $sheetData,
      NULL,
      'A1',
      true
    );
    classificationSalesFooterRows($totalsWorksheet);
    setClassificationSalesAsPercentage($totalsWorksheet, $totals);
    
    
    return $totalsWorksheet;
  }

  public function getGiftCardReportData(Request $request, ReportService $reportService)
  {
    $startDate = Carbon::parse($request->start_date);
    $endDate = Carbon::parse($request->end_date);
    $orgId = auth()->user()->organization_id;

    $data = $reportService->getGiftCardReportData($startDate, $endDate, $orgId);
    return response()->success($data);
  }
}
