<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title')</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        a { color: inherit; }
        .card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .08);
            padding: 1.75rem 2rem;
            width: 100%;
            max-width: 44rem;
        }
        .row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        .dot {
            flex: none;
            width: .85rem;
            height: .85rem;
            border-radius: 9999px;
            margin-top: .35rem;
        }
        .dot--ok { background: #22c55e; box-shadow: 0 0 0 4px rgba(34, 197, 94, .15); }
        .dot--fail { background: #ef4444; box-shadow: 0 0 0 4px rgba(239, 68, 68, .15); }
        .dot--idle { background: #9ca3af; box-shadow: 0 0 0 4px rgba(156, 163, 175, .15); }
        h1 { font-size: 1.35rem; font-weight: 600; }
        .subtle { color: #6b7280; font-size: .9rem; margin-top: .15rem; }

        /* /up page check list */
        .checks {
            list-style: none;
            margin-top: 1.25rem;
            border-top: 1px solid #e5e7eb;
        }
        .checks li {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .checks li:last-child { border-bottom: 0; }
        .checks .dot { width: .6rem; height: .6rem; margin-top: .3rem; box-shadow: none; }
        .check-body { flex: 1; min-width: 0; }
        .check-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
        }
        .check-label { font-weight: 600; font-size: .95rem; }
        .check-message { color: #6b7280; font-size: .85rem; margin-top: .1rem; }
        .check-uptime { color: #6b7280; font-size: .8rem; white-space: nowrap; }

        /* heartbeat bar */
        .beats {
            display: flex;
            gap: 3px;
            align-items: center;
            margin-top: .7rem;
            flex-wrap: nowrap;
            overflow: hidden;
        }
        .beat {
            flex: 1 1 0;
            min-width: 4px;
            max-width: 9px;
            height: 1.6rem;
            border-radius: 3px;
            background: #e5e7eb;
        }
        .beat--ok { background: #22c55e; }
        .beat--fail { background: #ef4444; }
        .beats--lg .beat { height: 2.5rem; border-radius: 4px; }
        .beats-empty { color: #9ca3af; font-size: .8rem; margin-top: .7rem; }

        /* details button */
        .details-btn {
            flex: none;
            font-size: .8rem;
            font-weight: 600;
            text-decoration: none;
            color: #2563eb;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            border-radius: .5rem;
            padding: .3rem .65rem;
        }
        .details-btn:hover { background: #dbeafe; }

        /* detail page */
        .back-link {
            display: inline-block;
            font-size: .85rem;
            color: #6b7280;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .back-link:hover { color: #1f2937; }
        .detail-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .badge {
            font-weight: 700;
            font-size: .8rem;
            letter-spacing: .03em;
            text-transform: uppercase;
            border-radius: 9999px;
            padding: .35rem .9rem;
        }
        .badge--ok { background: #dcfce7; color: #15803d; }
        .badge--fail { background: #fee2e2; color: #b91c1c; }
        .badge--idle { background: #f3f4f6; color: #6b7280; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(7rem, 1fr));
            gap: 1px;
            background: #e5e7eb;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            overflow: hidden;
            margin-top: 1.25rem;
        }
        .stat {
            background: #fff;
            padding: .9rem 1rem;
            text-align: center;
        }
        .stat-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #9ca3af;
        }
        .stat-value { font-size: 1.15rem; font-weight: 600; margin-top: .25rem; }
        .section-title {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #9ca3af;
            margin: 1.5rem 0 .6rem;
        }
        .chart { width: 100%; height: auto; display: block; }
        .footnote {
            color: #9ca3af;
            font-size: .78rem;
            margin-top: 1.5rem;
            border-top: 1px solid #f3f4f6;
            padding-top: .9rem;
        }

        /* detail page actions + flash */
        .detail-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .55rem;
        }
        .check-btn {
            font: inherit;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            color: #15803d;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            border-radius: .5rem;
            padding: .4rem .85rem;
        }
        .check-btn:hover { background: #bbf7d0; }
        .flash {
            border-radius: .6rem;
            padding: .7rem .9rem;
            font-size: .85rem;
            margin-bottom: 1rem;
        }
        .flash--ok { background: #dcfce7; color: #15803d; }
        .flash--fail { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <div class="card">
        @yield('body')
    </div>
</body>
</html>
