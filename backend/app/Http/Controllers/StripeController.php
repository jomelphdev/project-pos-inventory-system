<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Money\Money;
use Stripe\StripeClient;
use Throwable;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        $org = $request->user()->organization;
        $intent = $org->createSetupIntent();
        
        return response()->success(['session_id' => $intent->client_secret]);
    }

    public function getSubscriptionPlans() 
    {
       $stripe =  new StripeClient(config('services.stripe.secret'));
       $products = $stripe->products->all(['active' => true]);

       foreach ($products as $product)
       {
            $prices = $stripe->prices->all(['product' => $product->id, 'active' => true])->data;
            $monthlyPrice = array_reduce($prices, function ($a, $b) {
                return $a['unit_amount'] < $b['unit_amount'] ? $a : $b;
            }, $prices[0])->unit_amount;

            foreach ($prices as &$price)
            {
                $intervalData = $price->recurring;
                switch ($intervalData->interval)
                {
                    case 'month':
                        if ($intervalData->interval_count == 3)
                        {
                            $price->cycle = 'quarterly';
                            $price->savings = ($monthlyPrice * 3) - $price->unit_amount;
                            break;
                        }

                        $price->cycle = 'monthly';
                        break;
                    case 'year':
                        $price->cycle = 'annually';
                        $price->savings = ($monthlyPrice * 12) - $price->unit_amount;
                }
            }

            $product->prices = $prices;
        }
       
       return response()->success(['subscription_plans' => $products->data]);
    }

    public function changeSubscriptionPlan(Request $request) 
    {
        $request->validate([
            'plan_id' => 'required|string'
        ]);

        $newPlanId = $request->plan_id;
        $org = Organization::find($request->user()->organization_id);

        try
        {
            DB::beginTransaction();

            if (!$org->subscription) {
                $newPlan = $org->newSubscription('primary', $newPlanId);
                
                if ($org->trial_ends_at > now())
                {
                    $newPlan->trialUntil($org->trial_ends_at);
                }

                if ($org->defaultPaymentMethod())
                {
                    $newPlan = $newPlan->create($org->defaultPaymentMethod()->id);
                }
            }
            else if ($org->defaultPaymentMethod())
            {
                $newPlan = $org->subscription->swap($newPlanId);
            }

            DB::commit();
        }
        catch (Throwable $e)
        {
            DB::rollBack();
            return response()->error('Something went wrong while trying to change subscription plan.');
        }

        return response()->success(['new_plan' => $newPlan]);
    }

    public function cancelSubscription(Request $request) 
    {
        $org = Organization::find($request->user()->organization_id);

        try
        {
            DB::beginTransaction();
            $canceled = $org->subscription->cancel();
            DB::commit();
        }
        catch (Throwable $e)
        {
            DB::rollBack();
            return response()->error('Something went wrong while trying to cancel your subscription.');
        }

        return response()->success(['canceled_plan' => $canceled]);
    }

    public function updatePaymentMethod(Request $request)
    {
        $paymentMethod = $request->input('payment_method');
        $org = Organization::find($request->user()->organization_id);

        try
        {
            DB::beginTransaction();

            $this->createPaymentMethod($org, $paymentMethod);

            DB::commit();
        }
        catch (Throwable $e)
        {
            DB::rollBack();
            return response()->error('Something went wrong while trying to update your payment method.');
        }

        if ($org->subscription && $org->subscription->pastDue()) {
            $invoices = $org->invoicesIncludingPending()->where('status', 'past_due');

            foreach ($invoices as $invoice)
            {
                $invoice->pay();
            }
        }

        return response()->success();
    }

    private function createPaymentMethod(Organization $org, $paymentMethod) 
    {
        $org->deletePaymentMethods();
        $org->updateDefaultPaymentMethod($paymentMethod);
    }
}
