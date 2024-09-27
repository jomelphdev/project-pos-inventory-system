<?php

namespace App\Services;

use App\Exceptions\RetailRightException;
use App\Models\Item;
use App\Models\Organization;
use App\Models\PosOrderItem;
use App\Models\State;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    public static function createUser($newUser)
    {
        try
        {
            DB::beginTransaction();

            $newUser['password'] = Hash::make($newUser['password']);

            if (!($newUser instanceof User))
            {
                $user = new User($newUser);
            }
            else
            {
                $user = $newUser;
            }

            if (!isset($user['organization_id']) || is_null($user['organization_id']))
            {
                $paymentMethod = isset($user['payment_method']) ? $user['payment_method'] : null;
                $subscription = isset($user['subscription']) ? $user['subscription'] : null;
                $org = self::createOrganization($user, [
                    'payment_method' => $paymentMethod, 
                    'subscription' => $subscription
                ]);
            }

            $user->save();

            if (isset($org))
            {
                $org->preferences()->create(['owner_id' => $user->id]);
            }

            $role = 'owner';
            if (isset($newUser['role'])) 
            {
                $role = $newUser['role'];
            }

            if (isset($newUser['permissions']) && count($newUser['permissions']) > 0)
            {
                $user->givePermissionTo($newUser['permissions']);
            }

            if (isset($newUser['feedback']) && count($newUser['feedback']) > 0)
            {
                UserFeedbackService::createManyForUser($user, $newUser['feedback']);
            }

            $user->assignRole($role);
            $user['token'] = $user->createToken('token_name')->plainTextToken;

            event(new Registered($user));

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            throw $e;
        }

        return $user;
    }

    public static function createOrganization(User $user, $paymentData) {
        $org = new Organization(['trial_ends_at' => now()->addDays(14)]);
        $options = [
            'name' => $user->full_name,
            'email' => $user->email,
            'description' => 'RetailRight Customer'
        ];
        $org->createAsStripeCustomer($options);
        $org->save();
        $user->organization_id = $org->id;
        
        // If payment_method sign up for subscription via Stripe
        $paymentMethod = $paymentData['payment_method'];
        if (!is_null($paymentMethod)) 
        {
            $org->addPaymentMethod($paymentMethod);
            $org->newSubscription(config('services.stripe.product_id'), $paymentData['subscription'])
                ->trialDays(14)
                ->create($paymentMethod);
        }

        return $org;
    }

    public static function authenticate(string $username, string $password)
    {
        $user = User::withAllPreferences($username)->with('unreadNotifications')->first();
        
        if (!$user)
        {
            throw new RetailRightException('User does not exist.');
        } 
        else if (!Hash::check($password, $user->password))
        {
            throw new RetailRightException('Incorrect password.');
        }

        $user->append('user_permissions')->append('user_role')->append('subscription_required');
        $preferences = $user->organization->preferences;
        $preferences->append('employees_with_permissions');
        $preferences->classifications->where('deleted_at', null)->append('times_used');
        $preferences->conditions->where('deleted_at', null)->append('times_used');
        $preferences->discounts->where('deleted_at', null)->append('times_used');

        $preferences['states'] = State::all()->sortBy(['name', 'asc']);
        $user['token'] = $user->createToken('token_name')->plainTextToken;
    
        return $user;
    }
}