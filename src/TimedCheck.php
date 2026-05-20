<?php

namespace AlwaysCurious\AppUptime;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * Convenience base for checks that should populate `response_time_ms` —
 * the value the `/up` detail page's response-time chart and "Avg · 24h"
 * stat read off of.
 *
 * Extend this instead of {@see Check} and implement {@see TimedCheck::check()};
 * the elapsed wall-clock milliseconds are timed around your code and merged
 * into the {@see Result}'s meta as `response_time_ms`. Existing meta the check
 * sets itself is preserved (via {@see Result::appendMeta()}).
 *
 * Checks that don't need timing — or that have to time only a portion of the
 * probe — can extend Spatie's {@see Check} directly and write
 * `response_time_ms` into `meta()` themselves.
 */
abstract class TimedCheck extends Check
{
    final public function run(): Result
    {
        $startedAt = microtime(true);

        $result = $this->check();

        return $result->appendMeta([
            'response_time_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }

    /**
     * Implement the probe. Return a Spatie {@see Result} as normal — timing
     * is appended for you.
     */
    abstract protected function check(): Result;
}
