<?php

namespace App\Providers;

use App\Models\Produto;
use App\Models\Unidades;
use App\Observers\ProdutoObserver;
use App\Observers\UnidadesObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
        Schema::defaultStringLength(191);

        // Registrar observers
        Produto::observe(ProdutoObserver::class);
        Unidades::observe(UnidadesObserver::class);
    }
}
