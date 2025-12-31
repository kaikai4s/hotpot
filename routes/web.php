<?php

/**
 * Developed by eBrook Group.
 * Copyright © 2026 eBrook Group (https://www.ebrook.com.tw)
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

