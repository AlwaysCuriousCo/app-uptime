<?php

namespace AlwaysCurious\AppUptime\Http\Controllers;

use AlwaysCurious\AppUptime\HealthHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Spatie\Health\Checks\Check;
use Spatie\Health\Health;

/**
 * Backs the `/up` endpoint with an itemised health page: it renders each
 * registered check's most recent recorded status alongside a heartbeat bar
 * built from history.
 *
 * The page is read-only — checks are probed by the scheduled `health:check`
 * command, not on each request — so it stays fast and side-effect free.
 * Responds 200 unless a check's latest probe failed, in which case 500, so
 * uptime monitors and load balancers keep working against the same URL.
 */
class HealthController
{
    public function __invoke(Health $health, HealthHistory $history): Response
    {
        $heartbeatLimit = (int) config('uptime.heartbeats', 50);

        $checks = [];
        $healthy = true;

        foreach ($health->registeredChecks() as $check) {
            /** @var Check $check */
            $key = $check->getName();
            $latest = $history->latest($key);

            // A check with no history yet is "unknown", not failing — this
            // keeps `/up` green in the minute between deploy and first probe.
            if ($latest !== null && ! $latest->healthy) {
                $healthy = false;
            }

            $checks[] = [
                'key' => $key,
                'label' => $check->getLabel(),
                'latest' => $latest,
                'heartbeats' => $history->heartbeats($key, $heartbeatLimit),
                'uptime' => $history->uptime($key, now()->subDay()),
            ];
        }

        /** @var View $view */
        $view = view('uptime::up', [
            'healthy' => $healthy,
            'checks' => $checks,
        ]);

        return response(
            $view->render(),
            $healthy ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }
}
