<?php

namespace App\Providers;

use App\Models\Menu;
use App\Observers\MenuObserver;
use Illuminate\Database\Schema\Blueprint;
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
        // Konvensi prd §8.0 — kolom audit wajib di semua tabel.
        Blueprint::macro('auditColumns', function () {
            /** @var Blueprint $this */
            $this->uuid('created_by')->nullable();
            $this->uuid('updated_by')->nullable();
            $this->uuid('deleted_by')->nullable();
        });

        Menu::observe(MenuObserver::class);
    }
}
