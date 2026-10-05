<?php

namespace App\Providers;

use App\Models\Customer;
use App\View\NavigationComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');
        Paginator::defaultView('partials.pagination');
        View::composer('layouts.app', NavigationComposer::class);
        View::composer(['orders._new-modal', 'events.index'], function ($view) {
            $view->with('customerNames', Customer::orderBy('name')->pluck('name'));
        });
    }
}
