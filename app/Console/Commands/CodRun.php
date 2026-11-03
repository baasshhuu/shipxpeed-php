<?php

namespace App\Console\Commands;

use App\Support\Cod\CodRemittance;
use Illuminate\Console\Command;

/** Generates COD for newly delivered COD orders and settles low wallets from due COD. */
class CodRun extends Command
{
    protected $signature = 'cod:run';

    protected $description = 'COD remittance: generate rows for delivered COD orders and top up low wallets from due COD';

    public function handle(): int
    {
        $r = CodRemittance::refresh();
        $this->line(sprintf('[%s] COD created %d · wallets topped up %d (₹%s)',
            now()->format('Y-m-d H:i'), $r['created'], $r['sellers'], number_format($r['amount'], 2)));
        return self::SUCCESS;
    }
}
