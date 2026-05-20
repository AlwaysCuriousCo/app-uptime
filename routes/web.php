<?php

use AlwaysCurious\AppUptime\Http\Controllers\HealthController;
use AlwaysCurious\AppUptime\Http\Controllers\HealthDetailController;
use Illuminate\Support\Facades\Route;

/*
| The routes below are mounted under `config('uptime.routes.prefix')` (default
| `up`) by AppUptimeServiceProvider and named `uptime.*` so the controllers
| and views build URLs through route() rather than hard-coded paths.
*/

// Itemised health page: renders each registered dependency check's status.
Route::get('/', HealthController::class)->name('uptime.index');

// Per-check detail page: heartbeat history, uptime and response-time chart
// for a single dependency, addressed by its Check::getName().
Route::get('/{check}', [HealthDetailController::class, 'show'])->name('uptime.detail');

// "Check now": probe every check on demand (Spatie runs the full set) and
// redirect back to the detail page.
Route::post('/{check}/check', [HealthDetailController::class, 'checkNow'])->name('uptime.check');
