<?php

namespace App\Providers;

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
        $compiledViewPath = config('view.compiled');

        if (is_string($compiledViewPath) && ! is_dir($compiledViewPath)) {
            @mkdir($compiledViewPath, 0777, true);
        }
    }
}
