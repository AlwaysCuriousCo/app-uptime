<?php

namespace AlwaysCurious\AppUptime\Http\Controllers;

use AlwaysCurious\AppUptime\HealthHistory;
use AlwaysCurious\AppUptime\Models\HealthCheckRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\Checks\Check;
use Spatie\Health\Health;

/**
 * Backs `/up/{check}` — the per-check detail page, showing one dependency's
 * recorded history: heartbeat bar, uptime over several windows and a
 * response-time chart.
 */
class HealthDetailController
{
    public function show(string $check, Health $health, HealthHistory $history): View
    {
        $healthCheck = $this->resolveCheck($check, $health);

        abort_if($healthCheck === null, 404);

        $key = $healthCheck->getName();
        $heartbeatLimit = (int) config('uptime.heartbeats', 50);
        $heartbeats = $history->heartbeats($key, $heartbeatLimit);
        $retentionDays = (int) config('uptime.retention_days', 90);

        return view('uptime::detail', [
            'key' => $key,
            'label' => $healthCheck->getLabel(),
            'latest' => $history->latest($key),
            'heartbeats' => $heartbeats,
            'uptime' => [
                '24 hours' => $history->uptime($key, now()->subDay()),
                '30 days' => $history->uptime($key, now()->subDays(30)),
                '90 days' => $history->uptime($key, now()->subDays(90)),
            ],
            'avgResponseMs' => $history->averageResponseTime($key, now()->subDay()),
            'chart' => $this->buildChart($heartbeats),
            'footnote' => str_replace(
                ':days',
                (string) $retentionDays,
                (string) config('uptime.footnote', '')
            ),
        ]);
    }

    /**
     * Probe every check immediately, store the results and return to the
     * detail page — backs the "check now" button.
     *
     * spatie/laravel-health runs the full registered set per invocation; with
     * only a handful of cheap checks that is fine, and it keeps every probe on
     * the page fresh, not just this one.
     */
    public function checkNow(string $check, Health $health, HealthHistory $history): RedirectResponse
    {
        $healthCheck = $this->resolveCheck($check, $health);

        abort_if($healthCheck === null, 404);

        $key = $healthCheck->getName();

        // `--no-notification`: a manual button press should not fire alerts.
        Artisan::call('health:check', ['--no-notification' => true]);

        $latest = $history->latest($key);

        return redirect()
            ->route('uptime.detail', $key)
            ->with('checkedResult', [
                'healthy' => (bool) $latest?->healthy,
                'message' => $latest?->message ?? 'No result was recorded.',
            ]);
    }

    /**
     * Resolve a registered check by its stable key, or null when unknown.
     */
    private function resolveCheck(string $key, Health $health): ?Check
    {
        return $health->registeredChecks()
            ->first(fn (Check $check) => $check->getName() === $key);
    }

    /**
     * Build the response-time sparkline from the heartbeat series.
     *
     * Returns null when there are too few timed probes to draw a line.
     *
     * @param  Collection<int, HealthCheckRecord>  $heartbeats
     * @return array{width: int, height: int, line: string, area: string, min: int, max: int}|null
     */
    private function buildChart(Collection $heartbeats): ?array
    {
        $points = $heartbeats
            ->pluck('response_time_ms')
            ->filter(fn ($ms) => $ms !== null)
            ->map(fn ($ms) => (int) $ms)
            ->values();

        if ($points->count() < 2) {
            return null;
        }

        $width = 720;
        $height = 180;
        $padding = 12;
        $min = (int) $points->min();
        $max = (int) $points->max();
        $range = max($max - $min, 1);
        $lastIndex = $points->count() - 1;

        $coords = $points->map(function (int $ms, int $i) use ($width, $height, $padding, $min, $range, $lastIndex): string {
            $x = round($i / $lastIndex * $width, 2);
            $norm = ($ms - $min) / $range;
            $y = round($height - $padding - $norm * ($height - 2 * $padding), 2);

            return "{$x},{$y}";
        });

        $line = $coords->implode(' ');

        return [
            'width' => $width,
            'height' => $height,
            'line' => $line,
            'area' => "0,{$height} {$line} {$width},{$height}",
            'min' => $min,
            'max' => $max,
        ];
    }
}
