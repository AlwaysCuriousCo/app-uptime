<?php

namespace AlwaysCurious\AppUptime\Commands;

use Illuminate\Console\Command;

class AppUptimeCommand extends Command
{
    public $signature = 'app-uptime';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
