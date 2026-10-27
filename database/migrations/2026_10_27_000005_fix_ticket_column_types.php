<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The live tickets table was created with numeric columns for ticket_id, order_id and
 * status, so text like "SXT-00001", alphanumeric AWBs and "Pending" could never be saved.
 * Converts them to text columns. Existing numeric status values are mapped to names.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `tickets`
            MODIFY `ticket_id` VARCHAR(30) NULL DEFAULT NULL,
            MODIFY `order_id` VARCHAR(100) NULL DEFAULT NULL,
            MODIFY `status` VARCHAR(30) NOT NULL DEFAULT 'Pending'");

        DB::table('tickets')->whereIn('status', ['0', '1', ''])->update(['status' => 'Pending']);
        DB::table('tickets')->where('status', '2')->update(['status' => 'Resolved']);
        DB::table('tickets')->where('status', '3')->update(['status' => 'Rejected']);

        DB::table('tickets')->where(function ($q) {
            $q->whereNull('ticket_id')->orWhere('ticket_id', '')->orWhere('ticket_id', 'not like', 'SXT-%');
        })->orderBy('id')->get(['id'])->each(function ($row) {
            DB::table('tickets')->where('id', $row->id)
                ->update(['ticket_id' => 'SXT-' . str_pad($row->id, 5, '0', STR_PAD_LEFT)]);
        });
    }

    public function down(): void
    {
        // Not reversible: converting text back to numbers would lose data.
    }
};
