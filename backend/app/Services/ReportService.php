<?php

namespace App\Services;

use App\Models\CheckoutStation;
use App\Models\Consignor;
use App\Models\ConsignorInvoice;
use App\Models\PosOrder;
use App\Models\PosReturn;
use App\Models\Store;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Money\Money;

class ReportService
{
  public static function dailySalesDataFor($storeId, Carbon $date)
  {
    $orders = PosOrder::reportForStore($storeId, $date)->get();
    $returns = PosReturn::reportForStore($storeId, $date)->get();

    $orderTotals = self::getTotals($orders);
    $returnTotals = self::getTotals($returns, true);

    $orderTotals['items'] = $orders->sum('quantity_ordered');
    $returnTotals['items'] = $returns->sum('quantity_returned');

    $totals = self::reduceTotals($orderTotals, $returnTotals);
    $totals['items'] = $orderTotals['items'] - $returnTotals['items'];

    return [
      'orders' => $orders->all(),
      'order_totals' => $orderTotals,
      'returns' => $returns->all(),
      'return_totals' => $returnTotals,
      'totals' => $totals
    ];
  }

  public static function salesDataFor(array $storeIds, Carbon $start, Carbon $end, bool $withTotals=false)
  {
    $orders = collect();
    $returns = collect();
    $salesTable = [];
    $timezones = [];

    foreach ($storeIds as $id)
    {
      $orders = $orders->merge(PosOrder::reportForStore($id, $start, $end)->get()->sortByDesc('created_at'));
      $returns = $returns->merge(PosReturn::reportForStore($id, $start, $end)->get()->sortByDesc('created_at'));
      
      $timezones[$id] = Store::find($id)->state->timezone;
    }

    $ordersDateTable = $orders->mapToGroups(function ($o, $key) use ($timezones) {
      $date = new DateTime();
      $date->setTimezone(new DateTimeZone($timezones[$o->store_id]));
      $date->setTimestamp($o['created_at']->timestamp);
      $formattedDate = $date->format('m/d/Y');
      $o['date'] = $formattedDate;

      return [$formattedDate => $o];
    });

    $returnsDateTable = $returns->mapToGroups(function ($r, $key) use ($timezones) {
      $date = new DateTime();
      $date->setTimezone(new DateTimeZone($timezones[$r->store_id]));
      $date->setTimestamp($r['created_at']->timestamp);
      $formattedDate = $date->format('m/d/Y');
      $r['date'] = $formattedDate;
      
      return [$formattedDate => $r];
    });

    foreach ($ordersDateTable as $date => $orders)
    {
      $totals = self::getTotals($orders);
      $totals['sales'] = count($orders);
      $totals['date'] = $date;
      $ordersDateTable[$date] = $totals;
    }

    $salesTable = $ordersDateTable;

    foreach ($returnsDateTable as $date => $returns)
    {
      $totals = self::getTotals($returns, true);
      $totals['returns'] = count($returns);
      $returnsDateTable[$date] = $totals;
      $dataToAdd = $returnsDateTable[$date];

      if (isset($ordersDateTable[$date]))
      {
        $ordersTable = $ordersDateTable[$date];
        $returnsTable = $returnsDateTable[$date];
        $reducedTotals = self::reduceTotals($ordersTable, $returnsTable);
        $reducedTotals['sales'] = $ordersTable['sales'];
        $reducedTotals['returns'] = $returnsTable['returns'];
        $dataToAdd = $reducedTotals;
      }

      $dataToAdd['date'] = $date;
      $salesTable[$date] = $dataToAdd;
    }

    if ($withTotals)
    {
      $totalsCollection = collect();
      
      foreach ($salesTable as $date => $totals)
      {
        $totalsCollection->push($totals);
      }

      $totals = [
        'cash' => $totalsCollection->sum('cash'),
        'card' => $totalsCollection->sum('card'),
        'ebt' => $totalsCollection->sum('ebt'),
        'sub_total' => $totalsCollection->sum('sub_total'),
        'non_taxed_sub_total' => $totalsCollection->sum('non_taxed_sub_total'),
        'tax' => $totalsCollection->sum('tax'),
        'total' => $totalsCollection->sum('total'),
        'cost' => $totalsCollection->sum('cost'),
        'sales' => $totalsCollection->sum('sales'),
        'returns' => $totalsCollection->sum('returns'),
      ];

      return [
        'sales' => $salesTable->sortKeys(),
        'totals' => $totals
      ];
    }

    return $salesTable->sortKeys();
  }

  public function createConsignmentInvoice(int $organizationId, int $preferenceId, int $consignorId)
  {
    $consignmentData = $this->getConsignmentReportData($organizationId, $preferenceId);
    $consignorsData = $consignmentData->where('consignor_id', $consignorId)->first();

    try
    {
      DB::beginTransaction();

      $invoice = ConsignorInvoice::create([
        'organization_id' => $organizationId,
        'consignor_id' => $consignorId,
        'amount_paid' => $consignorsData->amount_owed,
        'amount_collected' => $consignorsData->consignment_sum,
      ]);

      DB::commit();
    }
    catch (Exception $e)
    {
      DB::rollBack();
      throw $e;
    }

    return $invoice;
  }

  public function getConsignmentInvoices(int $organizationId, int $page)
  {
    return ConsignorInvoice::where('organization_id', $organizationId)->paginate(30, ['*'], 'page', $page);
  }

  public function getConsignmentReportData(int $organizationId, int $preferenceId)
  {
    $consignors = Consignor::where('preference_id', $preferenceId)->get();
    $consignorIds = $consignors->pluck('id');
    $consignorsData = collect();
    
    foreach ($consignorIds as $id)
    {
      $recentInvoice = ConsignorInvoice::where('consignor_id', $id)->latest('id')->first();
      $date = $recentInvoice
        ? $recentInvoice->created_at->toDateTimeString()
        : null;
        
      $sqlStatement = "
      SELECT
        i.consignor_id, 
        SUM(sales) as sales, 
        SUM(consignment_sum) as consignment_sum,
        SUM(amount_owed - consignment_sum) as amount_owed
      FROM 
        (
          SELECT i.organization_id, i.consignor_id,
            (
              COALESCE((
                SELECT SUM(oi.quantity_ordered)
                FROM pos_order_items as oi
                WHERE oi.item_id=i.id" . ($date ? " AND oi.created_at > " . '"' . $date . '"' : '') . "
              ), 0) - 
              COALESCE((
                SELECT SUM(ri.quantity_returned)
                FROM pos_return_items as ri 
                WHERE ri.item_id=i.id" . ($date ? " AND ri.created_at > " . '"' . $date . '"' : '') . "
              ), 0)
            ) as sales,
            (
              COALESCE((
                SELECT SUM(oi.consignment_fee * oi.quantity_ordered)
                FROM pos_order_items as oi
                WHERE oi.item_id=i.id" . ($date ? " AND oi.created_at > " . '"' . $date . '"' : '') . "
              ), 0) -
              COALESCE((
                SELECT SUM(ri.consignment_fee * ri.quantity_returned)
                FROM pos_return_items as ri
                WHERE ri.item_id=i.id" . ($date ? " AND ri.created_at > " . '"' . $date . '"' : '') . "
              ), 0)
            ) AS consignment_sum,
            (
              COALESCE((
                SELECT SUM(oi.price * oi.quantity_ordered)
                FROM pos_order_items as oi
                WHERE oi.item_id=i.id" . ($date ? " AND oi.created_at > " . '"' . $date . '"' : '') . "
              ), 0) -
              COALESCE((
                SELECT SUM(oi.price * ri.quantity_returned)
                FROM pos_return_items as ri
                INNER JOIN pos_order_items as oi
                ON ri.pos_order_item_id=oi.id
                WHERE ri.item_id=i.id" . ($date ? " AND ri.created_at > " . '"' . $date . '"' : '') . "
              ), 0)
            ) AS amount_owed
          FROM items as i 
        ) as i
      WHERE i.organization_id=? AND i.consignor_id=? AND consignment_sum>=0 AND amount_owed>=0
      GROUP BY i.consignor_id;
      ";
      $query = DB::select($sqlStatement, [$organizationId, $id]);

      if (count($query) > 0)
      {
        $data = $query[0];
        $data->consignor_name = $consignors->where('id', $id)->first()->name;
        $consignorsData->push($data);
      }
    }

    return $consignorsData;
  }

  public function getCashDrawersReport(int $preferenceId)
  {
    $reportData = [];
    $drawers = CheckoutStation::where('preference_id', $preferenceId)->whereNotNull('drawer_balance')->get();

    foreach ($drawers as $drawer)
    {
      $data = $this->getReportDataForDrawer($drawer);

      if (!$data) continue;
      array_push($reportData, $data);
    }

    return $reportData;
  }

  public function getReportForDrawer(int $stationId)
  {
    $drawer = CheckoutStation::find($stationId);
    return $this->getReportDataForDrawer($drawer);
  }

  public function getReportDataForDrawer(CheckoutStation $drawer)
  {
    $data = [
      'id' => $drawer->id,
      'store_id' => $drawer->store_id,
      'name' => $drawer->name,
      'starting_balance' => $drawer->drawer_balance,
      'last_balanced' => $drawer->last_balanced
    ];

    $orders = PosOrder::select('created_by', 'checkout_station_id', 'cash', 'total', 'change')
      ->where('checkout_station_id', $drawer->id)
      ->where('created_at', '>=', $drawer->last_balanced)
      ->get();
    $returns = PosReturn::select('created_by', 'checkout_station_id', 'cash', 'total')
      ->where('checkout_station_id', $drawer->id)
      ->where('created_at', '>=', $drawer->last_balanced)
      ->get();

    $cash = $orders->sum('cash') - $returns->sum('cash');
    $currentBalance = $data['starting_balance'] + $cash - $orders->sum('change');
    $data['current_balance'] = $currentBalance;
    $data['difference'] = $currentBalance - $data['starting_balance'];

    $employeesData = [];
    $employeeOrderIds = $orders->pluck('created_by')->unique();
    $employeeReturnIds = $returns->pluck('created_by')->unique();
    foreach ($employeeOrderIds as $id)
    {
      $employeeOrders = $orders->where('created_by', $id);
      array_push($employeesData, [
        'user_id' => $id,
        'cash_transacted' => $employeeOrders->sum('cash') - $employeeOrders->sum('change'),
        'total_transacted' => $employeeOrders->sum('total'),
        'orders' => $employeeOrders->count()
      ]);
    }

    foreach ($employeeReturnIds as $id)
    {
      $employeeReturns = $returns->where('created_by', $id);

      if ($employeeOrderIds->contains($id))
      {
        $existingData = &$employeesData[array_search($id, array_column($employeesData, 'user_id'))];
        $existingData['cash_transacted'] -= $employeeReturns->sum('cash');
        $existingData['total_transacted'] -= $employeeReturns->sum('total');
        $existingData['returns'] = $employeeReturns->count();
        continue;
      }

      array_push($employeesData, [
        'user_id' => $id,
        'cash_transacted' => -$employeeReturns->sum('cash'),
        'total_transacted' => -$employeeReturns->sum('total'),
        'returns' => $employeeReturns->count()
      ]);
    }

    if ($employeesData == []) return null;

    $data['employee_data'] = $employeesData;

    return $data;
  }

  private static function getTotals(Collection $query, $isReturns=false)
  {
    $cash = Money::USD($query->sum('cash'));
    $card = Money::USD($query->sum('card'));
    $ebt = Money::USD($query->sum('ebt'));
    $subTotal = Money::USD($query->sum('sub_total'));
    $tax = Money::USD($query->sum('tax'));
    $total = Money::USD($query->sum('total'));

    

    $nonTaxedSubTotal = 0;
    if ($isReturns)
    {
      $cash = $cash->negative();
      $card = $card->negative();
      $ebt = $ebt->negative();
      $subTotal = $subTotal->negative();
      $tax = $tax->negative();
      $total = $total->negative();
      $cost = -$query->sum('return_cost');

      $query->each(function (PosReturn $posReturn) use (&$nonTaxedSubTotal) {
        $nonTaxedSubTotal -= $posReturn
          ->posOrderItems()
          ->where([
            ["is_taxed", false],
            ["is_ebt", false]
          ])
          ->get()
          ->sum('total');
      });
    }
    else 
    {
      $change = Money::USD($query->sum('change'));
      $cash = $cash->subtract($change);
      $cost = $query->sum('order_cost');

      $query->each(function (PosOrder $posOrder) use (&$nonTaxedSubTotal) {
        $nonTaxedSubTotal += $posOrder
          ->posOrderItems()
          ->where([
            ["is_taxed", false],
            ["is_ebt", false]
          ])
          ->get()
          ->sum('total');
      });
    }

    return [
      'cash' => $cash->getAmount(),
      'card' => $card->getAmount(),
      'ebt' => $ebt->getAmount(),
      'sub_total' => $subTotal->getAmount(),
      'non_taxed_sub_total' => $nonTaxedSubTotal,
      'tax' => $tax->getAmount(),
      'total' => $total->getAmount(),
      'cost' => $cost
    ];
  }

  private static function reduceTotals($minuend, $subtrahend)
  {
    return [
      'cash' => $minuend['cash'] + $subtrahend['cash'],
      'card' => $minuend['card'] + $subtrahend['card'],
      'ebt' => $minuend['ebt'] + $subtrahend['ebt'],
      'sub_total' => $minuend['sub_total'] + $subtrahend['sub_total'],
      'non_taxed_sub_total' => $minuend['non_taxed_sub_total'] + $subtrahend['non_taxed_sub_total'],
      'tax' => $minuend['tax'] + $subtrahend['tax'],
      'total' => $minuend['total'] + $subtrahend['total'],
      'cost' => $minuend['cost'] + $subtrahend['cost']
    ];
  }

  public function getGiftCardReportData($startDate, $endDate, $orgId) {
    // orders
    $orderData = PosOrder::select(
      'pos_orders.id AS pos_order_id',
      'pos_orders.cash',
      'pos_orders.ebt',
      'pos_orders.gc',
      'pos_orders.sub_total',
      'pos_orders.tax',
      'pos_orders.total',
      'pos_orders.amount_paid',
      DB::raw('SUM(poi.quantity_ordered) AS total_quantity_ordered')
    )
    ->join('pos_order_items as poi', 'poi.pos_order_id', '=', 'pos_orders.id')
    ->join('gift_cards as gc', 'gc.id', '=', 'pos_orders.gift_card_id')
    ->join('users as u', 'u.id', '=', 'gc.created_by')
    ->where('u.organization_id', '=', $orgId)
    ->where('pos_orders.created_at', '>=', $startDate)
    ->where('pos_orders.created_at', '<=', $endDate)
    ->groupBy(
      'pos_orders.id',
      'pos_orders.cash',
      'pos_orders.gc',
      'pos_orders.ebt',
      'pos_orders.sub_total',
      'pos_orders.tax',
      'pos_orders.total',
      'pos_orders.amount_paid'
    )
    ->orderBy('pos_orders.id', 'ASC')
    ->get();

    //returns
    $returnData = PosReturn::select(
      'po.id AS pos_order_id',
      'pos_returns.cash',
      'pos_returns.ebt',
      'pos_returns.gc',
      'pos_returns.sub_total',
      'pos_returns.tax',
      'pos_returns.total',
      DB::raw('SUM(pri.quantity_returned) AS total_quantity_returned')
    )
    ->join('pos_return_items as pri', 'pos_returns.id', '=', 'pri.pos_return_id')
    ->join('pos_order_items as poi', 'poi.id', '=', 'pri.pos_order_item_id')
    ->join('pos_orders as po', 'po.id', '=', 'poi.pos_order_id')
    ->join('gift_cards as gc', 'gc.id', '=', 'po.gift_card_id')
    ->join('users as u', 'u.id', '=', 'gc.created_by')
    ->where('u.organization_id', '=', $orgId)
    ->where('po.created_at', '>=', $startDate)
    ->where('po.created_at', '<=', $endDate)
    ->groupBy(
      'po.id',
      'pos_returns.cash',
      'pos_returns.ebt',
      'pos_returns.gc',
      'pos_returns.sub_total',
      'pos_returns.tax',
      'pos_returns.total',
    )
    ->orderBy('po.id', 'ASC')
    ->get();

    return [
      'orders' => $orderData,
      'orderTotals' => [
        'giftCardAmount' => $orderData->sum('gc'),
        'cash' => $orderData->sum('cash'),
        'ebt' => $orderData->sum('ebt'),
        'subTotal' => $orderData->sum('sub_total'),
        'tax' => $orderData->sum('tax'),
        'total' => $orderData->sum('total'),
        'quantityOrdered' => $orderData->sum('total_quantity_ordered')
      ],
      'returns' => $returnData,
      'returnTotals' => [
        'giftCardAmount' => -$returnData->sum('gc'),
        'cash' => -$returnData->sum('cash'),
        'ebt' => -$returnData->sum('ebt'),
        'subTotal' => -$returnData->sum('sub_total'),
        'tax' => -$returnData->sum('tax'),
        'total' => -$returnData->sum('total'),
        'quantityReturned' => -$returnData->sum('total_quantity_returned')
      ],
      'overallTotalSold' => $orderData->sum('gc'),
      'overallTotalReturned' => $returnData->sum('gc'),
      'itemSold' => $orderData->sum('total_quantity_ordered') - $returnData->sum('total_quantity_returned'),
      'itemReturned' => $returnData->sum('total_quantity_returned')
    ];
  }
  
}