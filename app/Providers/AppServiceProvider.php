<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;

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
        Blueprint::macro('catatWaktu', function () {
        $this->timestamp('dibuat_pada')->nullable();
        $this->timestamp('diubah_pada')->nullable();
    });

    // Pastikan session driver selalu pakai file (hindari database saat MySQL tidak running)
    config()->set('session.driver', 'file');
    }
}
