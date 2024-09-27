<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\QuickBooksAccount;
use Exception;
use QuickBooksOnline\API\Core\HttpClients\FaultHandler;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\Data\IPPIntuitEntity;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Exception\ServiceException;
use QuickBooksOnline\API\Facades\Account;
use QuickBooksOnline\API\Facades\JournalEntry;

class QuickBooksService
{
    private $organization;
    /** @var DataService */
    private $dataService;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
        $this->configureApiDataService();
    }

    private function configureApiDataService()
    {
        $this->dataService = DataService::Configure([
            'auth_mode' => 'oauth2',
            'ClientID' => config('services.intuit.quickbooks.client_id'),
            'ClientSecret' => config('services.intuit.quickbooks.client_secret'),
            'accessTokenKey' => $this->organization->quickbooks_access_token,
            'refreshTokenKey' => $this->organization->quickbooks_refresh_token,
            'QBORealmID' => $this->organization->quickbooks_realm_id,
            'baseUrl' => config('services.intuit.quickbooks.env')
        ]);
    }

    public static function createAuthRequest()
    {
        $dataService = DataService::Configure([
            'auth_mode' => 'oauth2',
            'ClientID' => config('services.intuit.quickbooks.client_id'),
            'ClientSecret' => config('services.intuit.quickbooks.client_secret'),
            'RedirectURI' => config('services.intuit.quickbooks.redirect_url'),
            'scope' => 'com.intuit.quickbooks.accounting',
            'baseUrl' => config('services.intuit.quickbooks.env')
        ]);
        $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
        return $OAuth2LoginHelper->getAuthorizationCodeURL();
    }

    public function refreshAccessToken()
    {
        $refreshToken = $this->organization->quickbooks_refresh_token;
        
        try
        {
            $OAuth2LoginHelper = new OAuth2LoginHelper(config('services.intuit.quickbooks.client_id'), config('services.intuit.quickbooks.client_secret'));
            $accessTokenObj = $OAuth2LoginHelper->refreshAccessTokenWithRefreshToken($refreshToken);
        }
        catch (ServiceException $e)
        {
            if (strpos($e->getMessage(), 'Error code="3200"'))
            {
                $this->organization->is_quickbooks_authenticated = false;
                $this->organization->save();
            }

            throw $e;
        }

        $this->organization->quickbooks_refresh_token = $accessTokenObj->getRefreshToken();
        $this->organization->quickbooks_access_token = $accessTokenObj->getAccessToken();
        $this->organization->save();

        $this->configureApiDataService();

        return $this->organization->quickbooks_access_token;
    }

    public function getUserAccounts()
    {
        $accounts = collect($this->dataService->FindAll('Account'));
        $error = $this->dataService->getLastError();

        if ($error)
        {
            return $this->handleException($error, [$this, 'getUserAccounts']);
        }

        return $accounts;
    }

    /**
     * @return QuickBooksOnline\API\Data\IPPIntuitEntity
     */
    public function createAccount(array $accountData)
    {
        $account = $this->dataService->Add(
            Account::create($accountData)
        );
        $error = $this->dataService->getLastError();

        if ($error)
        {
            return $this->handleException($error, [$this, 'createAccount'], [$accountData]);
        }

        return $account;
    }

    /**
     * @return QuickBooksOnline\API\Data\IPPIntuitEntity
     */
    public function updateAccount(IPPIntuitEntity $account, array $update)
    {
        $updatedAccount = $this->dataService->Update(
            Account::update($account, $update)
        );
        $error = $this->dataService->getLastError();

        if ($error)
        {
            return $this->handleException($error, [$this, 'updateAccount'], [$account, $update]);
        }

        return $updatedAccount;
    }

    /**
     * @return QuickBooksOnline\API\Data\IPPIntuitEntity
     */
    public function getJournalEntry(int $journalId)
    {
        $journal = $this->dataService->FindById('JournalEntry', $journalId);
        $error = $this->dataService->getLastError();

        if ($error)
        {
            return $this->handleException($error, [$this, 'getJournalEntry'], [$journalId]);
        }

        return $journal;
    }

    /**
     * @return QuickBooksOnline\API\Data\IPPIntuitEntity
     */
    public function createJournalEntry(array $journalData)
    {
        $journal = $this->dataService->Add(
            JournalEntry::create($journalData)
        );
        $error = $this->dataService->getLastError();

        if ($error)
        {
            return $this->handleException($error, [$this, 'createJournalEntry'], [$journalData]);
        }

        return $journal;
    }

    /**
     * @return QuickBooksOnline\API\Data\IPPIntuitEntity
     */
    public function updateJournalEntry(IPPIntuitEntity $journal, array $update)
    {
        $updatedJournal = $this->dataService->Update(
            JournalEntry::update($journal, $update)
        );
        $error = $this->dataService->getLastError();

        if ($error)
        {
            return $this->handleException($error, [$this, 'updateJournalEntry'], [$journal, $update]);
        }

        return $updatedJournal;
    }

    public function userHasRequiredAccounts()
    {
        $accountsToFind = [
            [
                'name_prefix' => 'Sales - ',
                'account_type' => 'Income',
                'account_sub_type' => 'SalesOfProductIncome',
                'account_type' => 'sales'
            ],
            [
                'name_prefix' => 'Cost of Goods - ',
                'account_type' => 'Cost of Goods Sold',
                'account_sub_type' => 'SuppliesMaterialsCogs',
                'account_type' => 'cost'
            ],
            [
                'name_prefix' => 'Inventory Asset - ',
                'account_type' => 'Other Current Asset',
                'account_sub_type' => 'Inventory',
                'account_type' => 'inventory_asset'
            ],
            [
                'name_prefix' => 'Sales Tax - ',
                'account_type' => 'Other Current Liability',
                'account_sub_type' => 'SalesTaxPayable',
                'account_type' => 'sales_tax'
            ],
        ];
        $stores = $this->organization->preferences->storesVisible;
        $accountsToUse = collect();
        $allAcounts = $this->getUserAccounts();
        
        $undepositedFundsAccount = $allAcounts->where('AccountSubType', 'UndepositedFunds')->first();
        $undepositedFundsAccount->account_type = 'cash';
        $accountsToUse->push($undepositedFundsAccount);
        

        foreach ($stores as $store)
        {
            foreach ($accountsToFind as $account)
            {
                $existingRecordOfAccount = QuickBooksAccount::
                    where('store_id', $store->id)
                    ->where('account_type', $account['account_type'])
                    ->first();

                if ($existingRecordOfAccount)
                {
                    $qbAccount = $allAcounts
                        ->where(
                            'Id', 
                            (string) $existingRecordOfAccount->quickbooks_account_id
                        )
                        ->first();
                }
                else
                {
                    $qbAccount = $this->createAccount([
                        'Name' => $account['name_prefix'] . $store->name,
                        'AccountType' => $account['account_type'],
                        'AccountSubType' => $account['account_sub_type']
                    ]);

                    $newAccountRecord = new QuickBooksAccount([
                        'store_id' => $store->id,
                        'quickbooks_account_id' => $qbAccount->Id,
                        'account_type' => $account['account_type']
                    ]);
                    $newAccountRecord->save();
                }

                $qbAccount->store_id = $store->id;
                $qbAccount->account_type = $account['account_type'];
                $accountsToUse->push($qbAccount);
            }
        }

        return $accountsToUse;
    }

    public function handleException(FaultHandler $e, callable $callback, array ...$args)
    {
        if ($this->checkIfInvalidGrant($e))
        {
            $this->refreshAccessToken();
            return call_user_func($callback, $args);
        }

        throw  new Exception(
            $e->getIntuitErrorCode() . ' ' 
            . $e->getIntuitErrorMessage() . ' '
            . $e->getIntuitErrorDetail()
        );
    }

    /** Returns true if invalid_grant error otherwise false */
    public static function checkIfInvalidGrant(FaultHandler $e)
    {
        if ($e->getIntuitErrorCode() == 3200 && strpos($e->getIntuitErrorDetail(), 'Token expired') !== false)
        {
            return true;
        }

        return false;
    }
}

?>