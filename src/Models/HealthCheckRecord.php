<?php

namespace AlwaysCurious\AppUptime\Models;

use AlwaysCurious\AppUptime\HealthCheckRecordStore;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * One persisted outcome of a health check probe.
 *
 * Rows are written by {@see HealthCheckRecordStore} after each scheduled
 * `health:check` run and read back to render the heartbeat bars and uptime
 * statistics on the `/up` pages.
 */
#[Fillable(['check_key', 'healthy', 'message', 'response_time_ms', 'checked_at'])]
class HealthCheckRecord extends Model
{
    use Prunable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'healthy' => 'boolean',
            'response_time_ms' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * Records older than the configured retention window are removed by the
     * scheduled `model:prune` run.
     *
     * @return Builder<HealthCheckRecord>
     */
    public function prunable(): Builder
    {
        $days = (int) config('uptime.retention_days', 90);

        return static::query()->where('checked_at', '<', now()->subDays($days));
    }

    /**
     * @param  Builder<HealthCheckRecord>  $query
     * @return Builder<HealthCheckRecord>
     */
    public function scopeForCheck(Builder $query, string $key): Builder
    {
        return $query->where('check_key', $key);
    }
}
