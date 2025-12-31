<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

