<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema; 


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
        // Use Bootstrap for pagination views
         Paginator::useBootstrap();
        // Set default string length for database schema to avoid issues with older MySQL versions
        Schema::defaultStringLength(191);
        
        // Define custom Blade directives for caching
        // Usage in Blade:
        // @cache_forever('cache_key')
        //     <!-- Expensive content to cache -->
        // @endcache_forever('cache_key')

        Blade::directive('cache_forever', function ($key) {
            return "<?php if (\$__html = Cache::get($key)) { echo \$__html; } else { ob_start(); ?>";
        });

        Blade::directive('endcache_forever', function ($key) {
            return "<?php \$__html = ob_get_clean(); Cache::forever($key, \$__html); echo \$__html; } ?>";
        });

        // Rate limiter for the public, unauthenticated MCP endpoint.
        RateLimiter::for('mcp', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
