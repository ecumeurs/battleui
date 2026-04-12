<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        $revision = 'unknown';
        if (function_exists('shell_exec')) {
            $rev = shell_exec('git rev-parse HEAD 2>/dev/null');
            if ($rev) {
                $revision = trim($rev);
            }
        }
        Log::info("Laravel API starting (rev: $revision)");
    }
}
