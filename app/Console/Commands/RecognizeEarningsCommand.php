<?php

namespace App\Console\Commands;

use App\Services\RecognizeEarnings;
use Illuminate\Console\Command;

class RecognizeEarningsCommand extends Command
{
    protected $signature = 'earnings:recognize';

    protected $description = 'Recognize due instructor earnings.';

    public function handle(RecognizeEarnings $service): int
    {
        $recognized = $service->run();

        $this->info("Recognized {$recognized} earning schedule row(s).");

        return self::SUCCESS;
    }
}
