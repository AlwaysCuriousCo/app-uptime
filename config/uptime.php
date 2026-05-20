<?php

/*
|--------------------------------------------------------------------------
| alwayscurious/app-uptime
|--------------------------------------------------------------------------
|
| Presentation settings for the itemised /up status page that this package
| renders on top of spatie/laravel-health.
|
| Check registration, scheduling, failure notifications and the result-store
| wiring all live in `config/health.php` (Spatie's config) — this file owns
| only what the UI renders.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The package registers `GET /up`, `GET /up/{check}` and
    | `POST /up/{check}/check` by default. Toggle `enabled` off to register
    | them yourself, change `prefix` to mount the pages elsewhere (e.g.
    | `status`), or add middleware to require auth.
    |
    */

    'routes' => [
        'enabled' => (bool) env('UPTIME_ROUTES_ENABLED', true),
        'prefix' => env('UPTIME_ROUTE_PREFIX', 'up'),
        'middleware' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Heartbeat window
    |--------------------------------------------------------------------------
    |
    | How many recent probe results to render in the heartbeat bar on the
    | `/up` page and the per-check detail page.
    |
    */

    'heartbeats' => (int) env('UPTIME_HEARTBEATS', 50),

    /*
    |--------------------------------------------------------------------------
    | History retention
    |--------------------------------------------------------------------------
    |
    | Recorded probe results older than this many days are removed by the
    | scheduled `model:prune` run. Keep it bounded so the table stays small.
    |
    */

    'retention_days' => (int) env('UPTIME_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Detail page footnote
    |--------------------------------------------------------------------------
    |
    | The explanatory line shown at the bottom of the per-check detail page.
    | The `:days` placeholder is replaced with the configured retention
    | window. Inline HTML (e.g. <code>) is rendered as-is.
    |
    */

    'footnote' => env(
        'UPTIME_FOOTNOTE',
        'Probed every minute by the scheduled <code>health:check</code> command. History is retained for :days days.'
    ),

];
