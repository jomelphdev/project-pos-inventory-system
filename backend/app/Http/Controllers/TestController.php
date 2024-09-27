<?php

namespace App\Http\Controllers;

use App\Models\Classification;
use App\Models\User;
use App\Services\SeedService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class TestController extends Controller
{
    public function seedTestUser() 
    {
        if (config("app.env") == "production")
        {
            return response()->error("Route can not be used in production.");
        }

        try
        {
            Artisan::call("e2e:seed-test-user");
        }
        catch (Exception $e)
        {
            return response()->error($e->getMessage());
        }

        return response()->success();
    }

    public function deleteTestUser()
    {
        if (config("app.env") == "production")
        {
            return response()->error("Route can not be used in production.");
        }

        $user = User::where("username", "TestUser")->first();
        $user->organization()->forceDelete();

        return response()->success();
    }

    public function clearTestData()
    {
        if (config("app.env") == "production")
        {
            return response()->error("Route can not be used in production.");
        }

        try
        {
            Artisan::call("e2e:seed-test-user");
        }
        catch(ModelNotFoundException $e)
        {
            return response()->error("Test user does not currently exist try running /api/test/seed-test-user.");
        }
        catch (Exception $e)
        {
            return response()->error($e->getMessage());
        }

        return response()->success();
    }

    public function seedSpecData(Request $request, SeedService $seedService)
    {
        $request->validate(['spec_name' => 'required|string']);
        $specName = $request->input("spec_name");

        switch ($specName)
        {
            case "user_create":
                $user = User::where("email", "swalton@test.com")->first();

                if ($user)
                {
                    $organization = $user->organization();
                
                    $user->organization->userFeedback()->forceDelete();
                    $user->preferences()->forceDelete();
                    $user->forceDelete();
                    $organization->forceDelete();
                }
                
                return response()->success();
            case "preferences":
                if ($testEmployee = User::where("email", "bwayne@test.wayne")->withTrashed()->first())
                {
                    $testEmployee->organization()->forceDelete();
                }

                $user = User::where("username", "TestUser")->first();
                
                $user->preferences->classifications()->each(function(Classification $classification) {
                    $classification->forceDelete();
                });
                $user->preferences->conditions()->forceDelete();
                $user->preferences->discounts()->forceDelete();
                $user->preferences->stores()->forceDelete();
                $user->preferences->receiptOptions()->forceDelete();
                $user->preferences->hide_pos_sales = false;
                $user->preferences->using_merchant_partner = false;
                $user->preferences->merchant_id = null;
                $user->preferences->save();

                return response()->success();
            case "item_edit":
                $user = User::where("username", "TestUser")->first();
                $user->organization->items()->forceDelete();
                
                return response()->success(["item" => $seedService->createBaseItem()]);
            case "pos_create_order":
                return $seedService->seedCreateOrderData();
            case "pos_create_order_payment_partner":
                return $seedService->seedPaymentPartnerOrderData();
            case "pos_create_return":
                return $seedService->seedCreateReturnData();
        }
    }
}
