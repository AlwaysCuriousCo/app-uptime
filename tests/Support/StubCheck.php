<?php

namespace AlwaysCurious\AppUptime\Tests\Support;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

/**
 * A deterministic check for tests. Flip {@see StubCheck::$healthy} to control
 * what `run()` returns; the response time is fixed so assertions can rely on
 * it.
 */
final class StubCheck extends Check
{
    public static bool $healthy = true;

    public static string $okMessage = 'All good';

    public static string $failMessage = 'Something broke';

    public function getName(): string
    {
        return 'stub';
    }

    public function getLabel(): string
    {
        return 'Stub Check';
    }

    public function run(): Result
    {
        $message = self::$healthy ? self::$okMessage : self::$failMessage;

        $result = self::$healthy
            ? Result::make()->ok()
            : Result::make()->failed($message);

        return $result
            ->shortSummary($message)
            ->meta(['response_time_ms' => 42]);
    }
}
