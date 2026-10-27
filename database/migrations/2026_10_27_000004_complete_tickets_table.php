<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the support tickets table complete. Safe to run on a server where
 * some of these columns already exist: each column is added only if missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        $columns = [
            'ticket_id'   => fn (Blueprint $t) => $t->string('ticket_id', 30)->nullable()->index(),
            'seller_id'   => fn (Blueprint $t) => $t->unsignedBigInteger('seller_id')->nullable()->index(),
            'order_id'    => fn (Blueprint $t) => $t->string('order_id', 100)->nullable(),
            'category_id' => fn (Blueprint $t) => $t->unsignedBigInteger('category_id')->nullable(),
            'subject'     => fn (Blueprint $t) => $t->string('subject', 100)->nullable(),
            'message'     => fn (Blueprint $t) => $t->text('message')->nullable(),
            'status'      => fn (Blueprint $t) => $t->string('status', 30)->default('Pending')->index(),
            'admin_reply' => fn (Blueprint $t) => $t->text('admin_reply')->nullable(),
            'replied_at'  => fn (Blueprint $t) => $t->timestamp('replied_at')->nullable(),
            'mail_sent_at'=> fn (Blueprint $t) => $t->timestamp('mail_sent_at')->nullable(),
            'deleted_at'  => fn (Blueprint $t) => $t->softDeletes(),
        ];

        foreach ($columns as $name => $add) {
            if (!Schema::hasColumn('tickets', $name)) {
                Schema::table('tickets', function (Blueprint $table) use ($add) {
                    $add($table);
                });
            }
        }

        // Give older tickets a ticket number so they show properly everywhere.
        DB::table('tickets')->whereNull('ticket_id')->orWhere('ticket_id', '')
            ->orderBy('id')->get(['id'])->each(function ($row) {
                DB::table('tickets')->where('id', $row->id)
                    ->update(['ticket_id' => 'SXT-' . str_pad($row->id, 5, '0', STR_PAD_LEFT)]);
            });
    }

    public function down(): void
    {
        foreach (['subject', 'admin_reply', 'replied_at', 'mail_sent_at'] as $name) {
            if (Schema::hasColumn('tickets', $name)) {
                Schema::table('tickets', fn (Blueprint $t) => $t->dropColumn($name));
            }
        }
    }
};
