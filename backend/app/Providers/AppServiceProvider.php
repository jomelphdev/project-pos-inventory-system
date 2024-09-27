<?php

namespace App\Providers;

use App\Contracts\ICardProcessor;
use App\Services\CardConnect;
use App\Models\Organization;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Cashier\Cashier;
use Laravel\Telescope\Telescope;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Cashier::ignoreMigrations();

        if ($this->app->environment('local')) {
            Telescope::ignoreMigrations();
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->bind(
            ICardProcessor::class,
            CardConnect::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        JsonResource::withoutWrapping();
        Cashier::useCustomerModel(Organization::class);
    }
}
