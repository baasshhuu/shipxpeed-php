<?php

namespace App\Console\Commands;

use App\Services\ShipcluesService;
use Illuminate\Console\Command;

class ShipcluesTest extends Command
{
    protected $signature = 'shipclues:test {from=110053} {to=500001} {--weight=500} {--cod}';
    protected $description = 'Read-only check of the Shipclues key: prints live rates and seller prices (books nothing)';

    public function handle(ShipcluesService $shipclues): int
    {
        $rates = $shipclues->getRatesForParams([
            'origin_pincode' => $this->argument('from'),
            'destination_pincode' => $this->argument('to'),
            'package_weight' => $this->option('weight'),
            'payment_type' => $this->option('cod') ? 'cod' : 'prepaid',
        ]);

        if (!$rates) {
            $this->error('No rates returned. Check SHIPCLUES_API_KEY and storage/logs.');
            return self::FAILURE;
        }

        $this->table(['courier_id', 'Courier', 'Zone', 'Seller price'],
            array_map(fn ($r) => [$r['courier_id'], $r['courierName'], $r['zone'], $r['courierCharge']], $rates));

        return self::SUCCESS;
    }
}
