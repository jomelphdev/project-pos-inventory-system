<?php

namespace App\Jobs;

use App\Events\LateReply;
use App\Models\Organization;
use App\Models\PosOrder;
use App\Models\PosReturn;
use App\Models\QuickBooksJournalEntry;
use App\Services\QuickBooksService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;

class ProcessUsersQuickBooksJournalEntry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $org;
    private $userId;
    private $qbService;
    private $moneyFormatter;
    private $date;
    private $dateString;

    public $tries = 3;
    public $backoff = 60 * 60 * 24;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Organization $org, Carbon $date=null, int $userId=null)
    {
        $this->org = $org;
        $this->userId = $userId;
        $this->qbService = new QuickBooksService($this->org);
        $this->moneyFormatter = new DecimalMoneyFormatter(new ISOCurrencies());

        $this->date = $date
            ? $date
            : Carbon::now();
        $this->dateString = new Carbon($date, 'America/Los_Angeles');
        $this->dateString = $this->dateString->toDateString();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {  
        try
        {
            $accounts = $this->qbService->userHasRequiredAccounts();

            $orders = collect();
            $returns = collect();
            $stores = $this->org->preferences->storesVisible;
            $journalEntry = [
                'Line' => []
            ];
            $salesTotal = Money::USD(0);

            if ($existingJournalEntry = $this->org->quickBooksJournalEntries()->where('for_date', $this->dateString)->first())
            {
                $existingJournal = $this->qbService->getJournalEntry($existingJournalEntry->quickbooks_journal_id);

                $journalEntry['Id'] = $existingJournal->Id;
                $journalEntry['SyncToken'] = $existingJournal->SyncToken;
                $journalEntry['sparse'] = false;
            }

            foreach ($stores as $store)
            {
                $orders = PosOrder::
                    reportForStore($store->id, $this->date)
                    ->with('posOrderItems')
                    ->get();
                $returns = PosReturn::
                    reportForStore($store->id, $this->date)
                    ->with('posReturnItems')
                    ->get();

                if ($orders->count() == 0 && $returns->count() == 0)
                {
                    continue;
                }

                $orderItems = collect();
                $returnItems = collect();

                foreach ($orders->pluck('posOrderItems') as $itemCollection)
                {
                    $orderItems = $orderItems->merge($itemCollection);
                }

                foreach ($returns->pluck('posReturnItems') as $itemCollection)
                {
                    $returnItems = $returnItems->merge($itemCollection);
                }

                $itemsCostSum = Money::
                    USD($orderItems->sum('cost'))
                    ->subtract(Money::USD($returnItems->sum('cost')));
                $subTotal = Money::
                    USD($orders->sum('sub_total'))
                    ->subtract(Money::USD($returns->sum('sub_total')));
                $storeSalesTotal = Money::
                    USD($orders->sum('total'))
                    ->subtract(Money::USD($returns->sum('total')));
                $salesTotal = $salesTotal->add($storeSalesTotal);

                $salesAccount =  $accounts
                    ->where('store_id', $store->id)
                    ->where('account_type', 'sales')
                    ->first();
                $costAccount = $accounts
                    ->where('store_id', $store->id)
                    ->where('account_type', 'cost')
                    ->first();
                $inventoryAccount = $accounts
                    ->where('store_id', $store->id)
                    ->where('account_type', 'inventory_asset')
                    ->first();
                $salesTaxAccount = $accounts
                    ->where('store_id', $store->id)
                    ->where('account_type', 'sales_tax')
                    ->first();
                
                if ((int) $subTotal->getAmount() > 0)
                {
                    array_push(
                        $journalEntry['Line'],
                        [
                            'JournalEntryLineDetail' => [
                                'PostingType' => 'Credit',
                                'AccountRef' => [
                                    'name' => $salesAccount->Name,
                                    'value' => $salesAccount->Id
                                ]
                            ],
                            'DetailType' => 'JournalEntryLineDetail',
                            'Amount' => $this->moneyFormatter->format($subTotal)
                        ],
                        [
                            'JournalEntryLineDetail' => [
                                'PostingType' => 'Credit',
                                'AccountRef' => [
                                    'name' => $salesTaxAccount->Name,
                                    'value' => $salesTaxAccount->Id
                                ]
                            ],
                            'DetailType' => 'JournalEntryLineDetail',
                            'Amount' => $this->moneyFormatter->format(
                                Money::USD($orders->sum('tax'))
                                    ->subtract(Money::USD($returns->sum('tax')))
                            )
                        ],
                    );
                }

                if ((int) $itemsCostSum->getAmount() > 0)
                {
                    array_push(
                        $journalEntry['Line'],
                        [
                            'JournalEntryLineDetail' => [
                                'PostingType' => 'Debit',
                                'AccountRef' => [
                                    'name' => $costAccount->Name,
                                    'value' => $costAccount->Id
                                ]
                            ],
                            'DetailType' => 'JournalEntryLineDetail',
                            'Amount' => $this->moneyFormatter->format($itemsCostSum)
                        ],
                        [
                            'JournalEntryLineDetail' => [
                                'PostingType' => 'Credit',
                                'AccountRef' => [
                                    'name' => $inventoryAccount->Name,
                                    'value' => $inventoryAccount->Id
                                ]
                            ],
                            'DetailType' => 'JournalEntryLineDetail',
                            'Amount' => $this->moneyFormatter->format($itemsCostSum)
                        ]
                    );
                }
            }

            if ($journalEntry['Line'] == [])
            {
                if ($this->userId)
                {
                    LateReply::dispatch([
                        'success' => true,
                        'user_id' => $this->userId,
                        'message' => 'No data to create QuickBooks journal entry for.'
                    ]);
                }

                return;
            }
            
            $cashAccount = $accounts->where('account_type', 'cash')->first();
            array_push(
                $journalEntry['Line'],
                [
                    'JournalEntryLineDetail' => [
                        'PostingType' => 'Debit',
                        'AccountRef' => [
                            'name' => $cashAccount->Name,
                            'value' => $cashAccount->Id
                        ]
                    ],
                    'DetailType' => 'JournalEntryLineDetail',
                    'Amount' => $this->moneyFormatter->format($salesTotal)
                ]
            );

            if ($existingJournalEntry)
            {
                $journal = $this->qbService->updateJournalEntry($existingJournal, $journalEntry);
            }
            else 
            {
                $journal = $this->qbService->createJournalEntry($journalEntry);
                QuickBooksJournalEntry::create([
                    'organization_id' => $this->org->id,
                    'quickbooks_journal_id' => $journal->Id,
                    'for_date' => $this->dateString
                ]);
            }

            if ($this->userId)
            {
                LateReply::dispatch([
                    'success' => true,
                    'user_id' => $this->userId,
                    'message' => 'QuickBooks journal entry has been created.'
                ]);
            }
            
            return $journal;
        }
        catch (Exception $e)
        {
            if ($this->userId)
            {
                LateReply::dispatch([
                    'success' => false,
                    'user_id' => $this->userId,
                    'message' => 'Error while trying to create QuickBooks journal entry.'
                ]);
            }
            
            throw $e;
        }
    }
}
