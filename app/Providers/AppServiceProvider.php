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

        // Ensure mandatory configuration is present (Crash Early Principle)
        $mandatoryConfig = [
            'services.upsilon.url' => 'UPSILON_API_URL',
            'services.upsilon.webhook_url' => 'UPSILON_WEBHOOK_URL',
        ];

        foreach ($mandatoryConfig as $key => $envVar) {
            if (empty(config($key))) {
                // In local environment or during maintenance (console), we might want to be more helpful, 
                // but in production/web we must crash early.
                if ($this->app->runningInConsole()) {
                    Log::warning("Mandatory configuration '$envVar' is missing. Some features may be unavailable.");
                    continue;
                }

                $message = "CRITICAL: Mandatory configuration '$envVar' is missing. The application cannot start.";
                Log::emergency($message);
                throw new \RuntimeException($message);
            }
        }
    }
}
