<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // renders <ul class="pagination"> which portal.css styles (no Tailwind needed)
        Paginator::useBootstrapFour();
    }
}