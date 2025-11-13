<?php

namespace InterWorks\PowerBI\Commands;

use Illuminate\Console\Command;

class PowerBICommand extends Command
{
    public $signature = 'laravel-powerbi';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
