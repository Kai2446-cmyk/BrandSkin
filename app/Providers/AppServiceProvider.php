<?php
namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        try {
            View::share('siteSettings', SiteSetting::pluck('value', 'key'));
        } catch (\Throwable $e) {
            View::share('siteSettings', collect([
                'web_name' => 'GlowSkin',
                'brand_tagline' => 'Beauty Brand',
                'logo' => 'assets/images/app_logo.png',
            ]));
        }
    }
}
