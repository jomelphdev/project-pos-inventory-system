<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

class StripeControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    // POST REQUESTS

    /**
     * @test
     */
    public function can_create_checkout_session()
    {
        $response = $this->post('/api/stripe/create-checkout');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'session_id'
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_change_subscription_plan()
    {
        $this->user->organization->createAsStripeCustomer([
            'email' => $this->user->email,
            'description' => '[FEATURE TEST] Can change subscription plan'
        ]);
        $this->user->organization->updateDefaultPaymentMethod('pm_card_visa');
        
        // FROM NO PLAN TO A PLAN
        $response = $this->post('/api/stripe/change-plan', ['plan_id' => 'price_1KW4EWLYnt28Bwslg4PAzW2x']);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'new_plan'
            ]
        ]);

        $this->user->refresh();

        $this->assertFalse(is_null($this->user->organization->subscription));

        // FROM ONE PLAN TO ANOTHER
        $response = $this->post('/api/stripe/change-plan', ['plan_id' => 'price_1KW4EcLYnt28BwslCXuhJI0c']);
            
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'new_plan'
            ]
        ]);

        $this->user->refresh();

        $this->assertFalse(is_null($this->user->organization->subscription));
        $this->assertEquals('price_1KW4EcLYnt28BwslCXuhJI0c', $this->user->organization->subscription->stripe_price);
    }

    /**
     * @test
     */
    public function can_cancel_subscription_plan()
    {
        $this->user->organization->createAsStripeCustomer([
            'email' => $this->user->email,
            'description' => '[FEATURE TEST] Can cancel subscription plan'
        ]);
        $this->user->organization
            ->newSubscription('primary', 'price_1KW4EcLYnt28BwslCXuhJI0c')
            ->create('pm_card_visa');
        
        $response = $this->post('/api/stripe/cancel-plan');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'canceled_plan'
            ]
        ]);
    }

    /**
     * @test
     */
    public function can_update_payment_method()
    {
        $this->user->organization->createAsStripeCustomer([
            'email' => $this->user->email,
            'description' => '[FEATURE TEST] Can update payment method'
        ]);
        
        $response = $this->post('/api/stripe/update-payment-method', ['payment_method' => 'pm_card_visa']);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data'
        ]);
    }

    // GET REQUESTS

    /**
     * @test
     */
    public function can_get_subscriptions()
    {
        $response = $this->get('/api/stripe/subscription-plans');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'subscription_plans'
            ]
        ]);
    }
}
