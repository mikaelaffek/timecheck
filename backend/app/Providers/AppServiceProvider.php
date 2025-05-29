<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Set the default timezone for the application to UTC
        date_default_timezone_set('UTC');
        
        // Configure Carbon to use English locale but still maintain Swedish date format
        Carbon::setLocale('en');
        Carbon::setToStringFormat('Y-m-d H:i:s');
        
        // Log that timezone was set to UTC
        \Log::info('Application timezone set to UTC, current time is ' . date('Y-m-d H:i:s'));
        
        // If you have string length issues with MySQL < 5.7.7
        Schema::defaultStringLength(191);
    }
}
