<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Nama hari/bulan di laporan dalam bahasa Indonesia, apa pun isi APP_LOCALE di .env.
        Carbon::setLocale('id');
    }
}
