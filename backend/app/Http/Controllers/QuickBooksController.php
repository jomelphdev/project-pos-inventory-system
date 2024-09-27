<?php

namespace App\Http\Controllers;

use App\Http\Resources\QuickBooksJournalEntryResource;
use App\Jobs\ProcessUsersQuickBooksJournalEntry;
use App\Models\QuickBooksJournalEntry;
use App\Services\QuickBooksService;
use Exception;
use Illuminate\Http\Request;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\Exception\ServiceException;

class QuickBooksController extends Controller
{
    public function createAuthRequest()
    {
        try
        {
            $authorizationCodeUrl = QuickBooksService::createAuthRequest();
            
        }
        catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to communicate with QuickBooks servers.');
        }

        return response()->success(['auth_url' => $authorizationCodeUrl]);
    }

    public function getAccessToken(Request $request)
    {
        $request->validate([
            'auth' => 'required|string',
            'realm_id' => 'required|string'
        ]);

        $authCode = $request->input('auth');
        $realmId = $request->input('realm_id');

        try 
        {
            $OAuth2LoginHelper = new OAuth2LoginHelper(
                config('services.intuit.quickbooks.client_id'),
                config('services.intuit.quickbooks.client_secret'),
                config('services.intuit.quickbooks.redirect_url')
            );
            $accessTokenObj = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($authCode, $realmId);
        }
        catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to retieve QuickBooks access token.');
        }

        $organization = $request->user()->organization;
        $organization->quickbooks_realm_id = $realmId;
        $organization->quickbooks_refresh_token = $accessTokenObj->getRefreshToken();
        $organization->quickbooks_access_token = $accessTokenObj->getAccessToken();
        $organization->is_quickbooks_authenticated = true;
        $organization->save();

        return response()->success(['access_token' => $organization->quickbooks_access_token]);
    }

    public function refreshAccessToken(Request $request)
    {
        $organization = $request->user()->organization;
        $qbService = new QuickBooksService($organization);
        
        try
        {
            $accessToken = $qbService->refreshAccessToken();
        }
        catch (ServiceException $e)
        {
            if (strpos($e->getMessage(), 'Error code="3200"'))
            {
                return response()->error('Re-authenticate QuickBooks in settings.');
            }

            return response()->error('Something went wrong while trying to refresh access token with QuickBooks.');
        }
        catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to refresh access token with QuickBooks.');
        }

        return response()->success(['access_token' => $accessToken]);
    }

    public function revokeApplicationAccess(Request $request)
    {
        $organization = $request->user()->organization;

        try
        {
            $OAuth2LoginHelper = new OAuth2LoginHelper(
                config('services.intuit.quickbooks.client_id'),
                config('services.intuit.quickbooks.client_secret')
            );
            $OAuth2LoginHelper->revokeToken($organization->quickbooks_refresh_token);
        }
        catch (Exception $e)
        {
            return response()->error("Something went wrong while trying to revoke RetailRight's acces to your QuickBooks, please contact support.");
        }

        $organization->quickbooks_realm_id = null;
        $organization->quickbooks_refresh_token = null;
        $organization->quickbooks_access_token = null;
        $organization->is_quickbooks_authenticated = false;
        $organization->save();

        return response()->success(['message' => 'Successfully un-linked QuickBooks.']);
    }

    public function generateJournalEntry(Request $request)
    {
        $user = $request->user();
        $org = $user->organization;

        ProcessUsersQuickBooksJournalEntry::dispatchAfterResponse($org, null, $user->id);

        return response()->success(['message' => 'QuickBooks journal entry has begun processing, we will notify you when its finished.']);
    }

    public function getExistingJournals(Request $request)
    {
        $org = $request->user()->organization;
        $page = $request->input('page', 1);

        $journals = QuickBooksJournalEntry::where('organization_id', $org->id)->paginate(30, ['*'], 'page', $page);
        
        return response()->success([
            'current_page' => $journals->currentPage(),
            'journals' => QuickBooksJournalEntryResource::collection($journals->items()),
            'to' => $journals->lastItem(),
            'total' => $journals->total()
        ]);
    }
}
