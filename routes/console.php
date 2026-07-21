<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about-glowskin', function () {
    $this->info('GlowSkin Laravel 13 conversion ready.');
});
