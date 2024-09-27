<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// STRIPE ROUTES - api/stripe
Route::middleware('auth:sanctum')->prefix('stripe')->group(function () {
    // - POST
    Route::post('create-checkout', 'StripeController@createCheckoutSession');
    Route::post('change-plan', 'StripeController@changeSubscriptionPlan');
    Route::post('cancel-plan', 'StripeController@cancelSubscription');
    Route::post('update-payment-method', 'StripeController@updatePaymentMethod');
    // - GET
    Route::get('subscription-plans', 'StripeController@getSubscriptionPlans');
});

// USER ROUTES - api/users
Route::middleware('auth:sanctum')->prefix('users')->group(function () {
    // - POST
    Route::post('create', 'UserController@store')->withoutMiddleware('auth:sanctum');
    Route::post('authenticate', 'UserController@authenticate')->withoutMiddleware('auth:sanctum');
    Route::post('update/{user_id}', 'UserController@update');
    Route::post('verify-password', 'UserController@verifyPassword');
    Route::post('notification', 'UserController@markNotificationRead');
    // - GET
    Route::get('{user_id}', 'UserController@show');
});

// ADMIN ROUTES - api/admin
Route::middleware('auth:sanctum')->prefix('admin')->group(function() {
    // - POST
    Route::post('notification', 'AdminController@createNotification');
});

// ITEM ROUTES - api/items
Route::middleware('auth:sanctum')->prefix('items')->group(function () {
    // - POST
    Route::post('create', 'ItemController@store');
    Route::post('update/{itemId}', 'ItemController@update');
    Route::post('delete/{itemId}', 'ItemController@destroy');
    Route::post('import', 'ItemController@importInventory');
    Route::post('calculate-price', 'ItemController@calculatePrice');
    Route::post('list/calculate-price', 'ItemController@calculatePriceForMultipleItems');
    // - GET
    Route::get('calculate-consignment-fee', 'ItemController@calculateConsignmentFee');
    Route::get('{id}', 'ItemController@show');
    Route::get("history/{itemId}", "ItemController@getItemHistory");

    // ITEM QUERIES - api/item/query
    Route::prefix('query')->group(function () {
        // - POST
        Route::post('/', 'ItemController@queryItems');
        Route::post('count', 'ItemController@queryItemsCount');
        Route::post('sku', 'ItemController@getBySku');
        Route::post('upc', 'ItemController@getByUpc');
        Route::post('upc-data', 'ItemController@getUpcData');
        // - GET
        Route::get('showcase', 'ItemController@queryItemsForShowcase')->withoutMiddleware('auth:sanctum');
        Route::get('title-conditions', 'ItemController@getUsedConditionsFromTitle');
    });
});

// ORDER ROUTES - api/orders
Route::middleware('auth:sanctum')->prefix('orders')->group(function () {
    // - POST
    Route::post('create', 'PosOrderController@store');
    Route::post('calculate-totals', 'PosOrderController@calculateOrderTotals');
    Route::post('calculate-payment', 'PosOrderController@calculatePayment');
    // - GET
    Route::get('organization', 'PosOrderController@getOrdersForOrganization');
    Route::get('return/{id}', 'PosOrderController@getOrderForReturn');
    Route::get('{id}', 'PosOrderController@show');

    Route::get('promotional-logs/{itemId}', 'PosOrderController@promotionalLogs');
});

// RETURN ROUTES - api/returns
Route::middleware('auth:sanctum')->prefix('returns')->group(function () {
    // - POST
    Route::post('create', 'PosReturnController@store');
    Route::post('calculate-refund', 'PosReturnController@calculateRefund');
});

// REPORT ROUTES - api/reports
Route::middleware('auth:sanctum')->prefix('reports')->group(function () {

    // REPORT DATA ROUTES - api/reports/data - for front end report ui.
    Route::prefix('data')->group(function () {
        // - POST
        Route::post('daily-sales', 'ReportController@getDailySalesReportData');
        Route::post('sales', 'ReportController@getSalesReportData');
        Route::post('inventory', 'ReportController@getInventoryReportData');
        Route::post('gift-card-report', 'ReportController@getGiftCardReportData');

        // - GET
        // Route::get('item-sales', 'ReportController@getItemSalesReportData');
        Route::get('consignment', 'ReportController@getConsignmentReportData');
        Route::get('drawers', 'ReportController@getCashDrawersReport');
    });

    // - POST
    Route::post('daily-sales', 'ReportController@getDailySalesReport');
    Route::post('sales', 'ReportController@getSalesReport');
    Route::post('item-sales', 'ReportController@getItemSalesReport');
    Route::post('classification-sales', 'ReportController@getClassificationSalesReport');
    Route::post('inventory', 'ReportController@getInventoryReport');
    Route::post('consignment-invoice', 'ReportController@createConsignmentInvoice');
    Route::post('drawer-balance', 'ReportController@setNewDrawerBalance');
    Route::post('{id}/regenerate', 'ReportController@regenerateReport');

    // - GET
    Route::get('consignment-invoices', 'ReportController@getConsignmentInvoices');
    Route::get('directories', 'ReportController@getReportDirectories');
    Route::get('download', 'ReportController@download');

    // - DELETE
    Route::delete('{id}', 'ReportController@delete');
});

// MANIFEST ROUTES - api/manifests
Route::middleware('auth:sanctum')->prefix('manifests')->group(function () {
    // - POST
    Route::post('upload', 'ManifestController@store');
    Route::post('query/{manifestId}', 'ManifestController@queryManifestItemsOnManifest');
    Route::post('archive/{manifestId}', 'ManifestController@archiveManifest');
    // - GET
    Route::get('/', 'ManifestController@queryManifests');
});

// PREFERENCE ROUTES - api/preferences
Route::middleware('auth:sanctum')->prefix('preferences')->group(function () {
    // - POST
    Route::post('merchant', 'PreferenceController@getMerchantInfo')->withoutMiddleware('auth:sanctum');
    Route::post('seed-default', 'PreferenceController@seedDefaultPreferences');

    // PREFERENCE UPDATE ROUTES - api/preferences/update
    Route::prefix('update')->group(function () {
        Route::post('', 'PreferenceController@update');
        Route::post('preference', 'PreferenceController@updatePreference');
        Route::post('multiple', 'PreferenceController@updateMultiple');
    });
    // - GET
    Route::get('/', 'PreferenceController@show');
    Route::get('merchant', 'PreferenceController@getMerchantInfo')->withoutMiddleware('auth:sanctum');
});

// ORGANIZATION ROUTES - api/organization
Route::middleware('auth:sanctum')->prefix('organization')->group(function () {
    // - POST
    Route::post('save-slug', 'OrganizationController@saveSlug');
});

// CARD PROCESSING ROUTES - api/card
Route::middleware('auth:sanctum')->prefix('card')->group(function () {
    // - GET
    Route::get('verify', 'CardProcessingController@verifyMerchant');
    Route::get('terminals', 'CardProcessingController@getTerminals');
    Route::get('connect', 'CardProcessingController@connectToTerminal');
    Route::get('disconnect', 'CardProcessingController@disconnectTerminal');
    // - POST
    // Route::post('terminal-transaction', 'CardProcessingController@authorizeTerminalTransaction');
});

// IMAGE ROUTES - api/images
Route::middleware('auth:sanctum')->prefix('images')->group(function () {
    // - POST
    Route::post('upload', 'ImageController@store');
    Route::post('blog-upload', 'ImageController@uploadBlogImage');
});

// QZTRAY ROUTES - api/qz
Route::middleware('auth:sanctum')->prefix('qz')->group(function () {
    // - GET
    Route::get('sign', 'QzController@signData');
    Route::get('cert', 'QzController@getCert');
});

// QUICKBOOK ROUTES - api/quickbooks
Route::middleware('auth:sanctum')->prefix('quickbooks')->group(function () {
    // - POST
    Route::post('generate-journal', 'QuickBooksController@generateJournalEntry');
    Route::post('revoke', 'QuickBooksController@revokeApplicationAccess');
    // - GET
    Route::get('authorize', 'QuickBooksController@createAuthRequest');
    Route::get('access-token', 'QuickBooksController@getAccessToken');
    Route::get('refresh-token', 'QuickBooksController@refreshAccessToken');
    Route::get('journals', 'QuickBooksController@getExistingJournals');
});

// BLOG ROUTES - api/blog
Route::middleware('auth:sanctum')->prefix('blog')->group(function () {
    // - POST
    Route::post('create', 'BlogPostController@store')->withoutMiddleware('auth:sanctum');
    Route::post('delete', 'BlogPostController@destroy');
    // - GET
    Route::prefix('get')->withoutMiddleware('auth:sanctum')->group(function () {
        Route::get('', 'BlogPostController@index');
        Route::get('paths', 'BlogPostController@paths');
        Route::get('{slug}', 'BlogPostController@show');
    });
});

// GIFT CARDS ROUTES - api/gift
Route::middleware('auth:sanctum')->prefix('gift')->group(function () {
    // - POST
    Route::post("gift-card", "GiftCardController@store");
    Route::post('gift-card-check-balance', 'GiftCardController@checkGiftCardBalance');

    // PUT
    Route::put("gift-card/{id}", "GiftCardController@update");
    Route::put("activate-deactivate/{id}", "GiftCardController@activateDeactivate");
    Route::put("update-gift-card-balance/{id}", "GiftCardController@updateGiftCardBalance");

    // - GET
    Route::get('gift-cards', 'GiftCardController@index');

    // Top up
    Route::prefix('top-up')->group(function () {
        Route::get('/{giftCardId}', 'GiftCardTopUpController@index');
    });
});

// VERIFICATION ROUTES - api/verification
Route::prefix('verification')->group(function () {
    // - POST
    Route::post('resend/{user_id}', 'VerificationController@resend')->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
    // - GET
    Route::get('verify/{id}/{hash}', 'VerificationController@verify')->middleware(['signed'])->name('verification.verify');
});

// TEST ROUTES - api/test
Route::prefix("test")->group(function () {
    // POST
    Route::post("seed-test-user", "TestController@seedTestUser");
    Route::post("clear", "TestController@clearTestData");
    Route::post("delete-test-user", "TestController@deleteTestUser");
    Route::post("seed-spec-data", "TestController@seedSpecData");
});