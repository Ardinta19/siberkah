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
        \Illuminate\Support\Facades\View::composer('partials.product-search-fields', function ($view) {
            $view->with('searchCategories', $view->getData()['kategoris'] ?? \App\Models\Kategori::aktif()->terurut()->get(['id','nama']));
        });
        Blueprint::macro('catatWaktu', function () {
            $this->timestamp('dibuat_pada')->nullable();
            $this->timestamp('diubah_pada')->nullable();
        });
    }
}
