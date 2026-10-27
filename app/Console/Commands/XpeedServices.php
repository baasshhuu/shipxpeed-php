<?php

namespace App\Console\Commands;

use App\Support\Xpeed\Kit;
use App\Support\Xpeed\Protection;
use App\Support\Xpeed\Reports;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Hourly Seller Control Suite run:
 *  - shipment protection fees for new protected shipments (+ refunds for cancelled ones)
 *  - scheduled report e-mails that are due (sent after 8 am IST)
 */
class XpeedServices extends Command
{
    protected $signature = 'xpeed:services {--reports-only} {--protection-only}';
    protected $description = 'Shipment protection fees and scheduled seller reports';

    public function handle(): int
    {
        if (!$this->option('reports-only')) {
            $r = Protection::run();
            $this->info("Protection: charged {$r['charged']}, refunded {$r['refunded']}");
        }

        if (!$this->option('protection-only') && Kit::ready('xp_report_schedules') && now('Asia/Kolkata')->hour >= 8) {
            foreach (DB::table('xp_report_schedules')->where('active', 1)->get() as $s) {
                if (!Reports::due($s)) {
                    continue;
                }
                try {
                    $res = Reports::send($s);
                } catch (\Throwable $e) {
                    $res = 'Failed: ' . mb_substr($e->getMessage(), 0, 200);
                }
                DB::table('xp_report_schedules')->where('id', $s->id)->update(['last_sent_at' => now(), 'last_result' => mb_substr($res, 0, 250), 'updated_at' => now()]);
                $this->line("Report #{$s->id}: {$res}");
            }
        }
        return self::SUCCESS;
    }
}
