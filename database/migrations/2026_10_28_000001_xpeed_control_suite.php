<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seller Control Suite (NDR center, courier preferences & rules, reports scheduler,
 * branded tracking, shipment protection & claims, early COD). Only creates new tables
 * and adds nullable columns, so it is safe to run on the live database and re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        // NDR actions table (normally created by the WhatsApp migration) + where the action came from
        if (!Schema::hasTable('ndr_responses')) {
            Schema::create('ndr_responses', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('order_id')->index();
                $t->unsignedBigInteger('seller_id')->nullable()->index();
                $t->string('awb_number', 60)->nullable();
                $t->string('token', 64)->unique();
                $t->timestamp('expires_at')->nullable();
                $t->string('action', 30)->nullable();
                $t->date('reattempt_date')->nullable();
                $t->text('address')->nullable();
                $t->string('landmark', 190)->nullable();
                $t->string('phone', 16)->nullable();
                $t->string('remark', 255)->nullable();
                $t->string('status', 30)->default('awaiting_buyer');
                $t->text('courier_response')->nullable();
                $t->timestamp('responded_at')->nullable();
                $t->timestamps();
            });
        }
        Schema::table('ndr_responses', function (Blueprint $t) {
            if (!Schema::hasColumn('ndr_responses', 'source')) {
                $t->string('source', 20)->nullable();          // buyer | seller | admin
            }
            if (!Schema::hasColumn('ndr_responses', 'handled_by')) {
                $t->unsignedBigInteger('handled_by')->nullable();
            }
            if (!Schema::hasColumn('ndr_responses', 'ops_note')) {
                $t->string('ops_note', 255)->nullable();
            }
        });

        if (!Schema::hasTable('xp_settings')) {
            Schema::create('xp_settings', function (Blueprint $t) {
                $t->id();
                $t->string('key', 100)->unique();
                $t->text('value')->nullable();
                $t->timestamps();
            });
        }

        // Courier preferences: one row per seller
        if (!Schema::hasTable('xp_courier_prefs')) {
            Schema::create('xp_courier_prefs', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->unique();
                $t->string('mode', 20)->default('cheapest');   // cheapest | fastest | custom
                $t->json('brand_order')->nullable();            // ["Delhivery","Xpressbees",...]
                $t->text('blocked_pincodes')->nullable();        // comma separated
                $t->json('courier_blocks')->nullable();          // {"Bluedart": ["110001", ...]}
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('xp_courier_rules')) {
            Schema::create('xp_courier_rules', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->string('name', 120);
                $t->unsignedInteger('priority')->default(10);
                $t->boolean('active')->default(true);
                $t->string('payment', 10)->default('any');      // any | cod | prepaid
                $t->decimal('min_value', 12, 2)->nullable();
                $t->decimal('max_value', 12, 2)->nullable();
                $t->unsignedInteger('min_weight_g')->nullable();
                $t->unsignedInteger('max_weight_g')->nullable();
                $t->string('zones', 40)->nullable();             // "A,B"
                $t->text('pincodes')->nullable();                // comma separated delivery pincodes
                $t->string('courier_brand', 60);                 // e.g. Delhivery
                $t->string('courier_mode', 10)->default('any'); // any | surface | air
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('xp_report_schedules')) {
            Schema::create('xp_report_schedules', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->string('report', 30);                        // orders | ndr | cod | wallet | rto
                $t->string('frequency', 10);                     // daily | weekly | monthly
                $t->string('emails', 500);
                $t->boolean('active')->default(true);
                $t->timestamp('last_sent_at')->nullable();
                $t->string('last_result', 255)->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('xp_tracking_brands')) {
            Schema::create('xp_tracking_brands', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->unique();
                $t->boolean('active')->default(true);
                $t->string('brand_name', 80)->nullable();
                $t->string('logo_path', 255)->nullable();
                $t->string('color', 7)->default('#4f8cff');
                $t->string('support_phone', 20)->nullable();
                $t->string('support_email', 120)->nullable();
                $t->string('website', 190)->nullable();
                $t->string('banner_text', 160)->nullable();
                $t->string('banner_link', 255)->nullable();
                $t->timestamps();
            });
        }

        // Shipment protection + early COD choice per seller
        if (!Schema::hasTable('xp_seller_services')) {
            Schema::create('xp_seller_services', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->unique();
                $t->boolean('protection_on')->default(false);
                $t->timestamp('protection_since')->nullable();
                $t->string('early_cod_plan', 10)->nullable();   // d2 | d3 | d4
                $t->string('early_cod_status', 20)->nullable(); // requested | active | rejected | stopped
                $t->timestamp('early_cod_requested_at')->nullable();
                $t->timestamp('early_cod_decided_at')->nullable();
                $t->string('early_cod_note', 255)->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('xp_protection_charges')) {
            Schema::create('xp_protection_charges', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->unsignedBigInteger('order_id')->unique();
                $t->string('awb', 60)->nullable()->index();
                $t->decimal('declared_value', 12, 2);
                $t->decimal('fee', 10, 2);
                $t->unsignedBigInteger('recharge_id')->nullable();
                $t->string('status', 20)->default('charged');    // charged | refunded
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('xp_claims')) {
            Schema::create('xp_claims', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('seller_id')->index();
                $t->unsignedBigInteger('order_id')->index();
                $t->string('awb', 60)->nullable();
                $t->string('type', 20);                          // lost | damaged | missing_item
                $t->text('description');
                $t->json('proofs')->nullable();
                $t->decimal('declared_value', 12, 2)->default(0);
                $t->decimal('claim_amount', 12, 2)->default(0);
                $t->boolean('protected')->default(false);
                $t->string('status', 20)->default('pending');    // pending | approved | rejected
                $t->decimal('approved_amount', 12, 2)->nullable();
                $t->unsignedBigInteger('recharge_id')->nullable();
                $t->string('admin_remark', 500)->nullable();
                $t->unsignedBigInteger('reviewed_by')->nullable();
                $t->timestamp('reviewed_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['xp_claims', 'xp_protection_charges', 'xp_seller_services', 'xp_tracking_brands',
                  'xp_report_schedules', 'xp_courier_rules', 'xp_courier_prefs', 'xp_settings'] as $t) {
            Schema::dropIfExists($t);
        }
        foreach (['source', 'handled_by', 'ops_note'] as $c) {
            if (Schema::hasTable('ndr_responses') && Schema::hasColumn('ndr_responses', $c)) {
                Schema::table('ndr_responses', fn (Blueprint $t) => $t->dropColumn($c));
            }
        }
    }
};
