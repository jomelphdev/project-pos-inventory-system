<?php

namespace App\Providers;

use App\Models\Item;
use App\Models\PosOrder;
use App\Models\PosReturn;
use App\Models\User;
use App\Policies\ItemPolicy;
use App\Policies\PosOrderPolicy;
use App\Policies\PosReturnPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Item::class => ItemPolicy::class,
        PosOrder::class => PosOrderPolicy::class,
        PosReturn::class => PosReturnPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
