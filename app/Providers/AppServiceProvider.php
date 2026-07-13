<?php

namespace App\Providers;

use App\Models\Menu;
use App\Observers\MenuObserver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
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

        // Workaround bug Mary UI 2.9: Tab.php memanggil <x-badge> tanpa prefix,
        // tapi 'badge' tidak masuk daftar alias internal provider Mary — sehingga
        // dengan prefix 'mary-' semua halaman ber-x-mary-tab gagal compile.
        Blade::component('badge', \Mary\View\Components\Badge::class);
    }
}
