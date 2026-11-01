<?php

namespace App\Console\Commands;

use App\Support\WeightAutoFetch;
use Illuminate\Console\Command;

/** Hourly: fetch courier re-weighs and charge weight discrepancies automatically. */
class WeightAutoFetchCommand extends Command
{
    protected $signature = 'weight:auto-fetch {--limit=} {--force : run even when switched off in admin}';
    protected $description = 'Fetch courier re-weighed weights (Shipclues / Delhivery) and debit weight discrepancies';

    public function handle(): int
    {
        $r = WeightAutoFetch::run($this->option('limit') ? (int) $this->option('limit') : null, (bool) $this->option('force'));
        $this->line(now()->toDateTimeString() . ' ' . json_encode($r));
        return self::SUCCESS;
    }
}
