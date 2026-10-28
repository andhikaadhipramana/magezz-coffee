<?php

namespace App\Providers;

use App\Models\Order;
use App\Services\PublicCartService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer(['layouts.public', 'layouts.app'], function ($view) {
            $view->with('publicCartCount', app(PublicCartService::class)->count());
            $view->with('onlineOrdersPending', Order::query()->where('status', Order::STATUS_PENDING)->count());
        });
    }
}
