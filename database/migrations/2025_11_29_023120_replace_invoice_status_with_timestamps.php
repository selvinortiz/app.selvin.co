<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add new timestamp columns
            $table->timestamp('sent_at')->nullable()->after('amount');
            $table->timestamp('paid_at')->nullable()->after('sent_at');
        });

        // Migrate existing data
        // Get Mod Creative client ID by code
        $modCreativeClientId = DB::table('clients')
            ->where('code', '224MOD')
            ->value('id');

        // Migrate Draft invoices: sent_at = null, paid_at = null
        DB::table('invoices')
            ->where('status', 'draft')
            ->update([
                'sent_at' => null,
                'paid_at' => null,
            ]);

        // Migrate Sent invoices: sent_at = date (as datetime with 13:00), paid_at = null
        DB::table('invoices')
            ->where('status', 'sent')
            ->update([
                'sent_at' => DB::raw("CONCAT(date, ' 13:00:00')"),
                'paid_at' => null,
            ]);

        // Migrate Paid invoices
        // First, set sent_at for all paid invoices
        DB::table('invoices')
            ->where('status', 'paid')
            ->update([
                'sent_at' => DB::raw("CONCAT(date, ' 13:00:00')"),
            ]);

        // For Mod Creative: paid_at = date + 7 days (as datetime with 13:00)
        if ($modCreativeClientId) {
            DB::table('invoices')
                ->where('status', 'paid')
                ->where('client_id', $modCreativeClientId)
                ->update([
                    'paid_at' => DB::raw("DATE_ADD(CONCAT(date, ' 13:00:00'), INTERVAL 7 DAY)"),
                ]);
        }

        // For all other clients: paid_at = due_date (as datetime with 13:00)
        $otherPaidQuery = DB::table('invoices')
            ->where('status', 'paid');

        if ($modCreativeClientId) {
            $otherPaidQuery->where('client_id', '!=', $modCreativeClientId);
        }

        $otherPaidQuery->update([
            'paid_at' => DB::raw("CONCAT(due_date, ' 13:00:00')"),
        ]);

        // Add indexes for performance
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('sent_at');
            $table->index('paid_at');
        });

        // Remove status column
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Re-add status column
            $table->string('status')->after('amount');
        });

        // Migrate data back to status
        DB::table('invoices')
            ->whereNull('sent_at')
            ->whereNull('paid_at')
            ->update(['status' => 'draft']);

        DB::table('invoices')
            ->whereNotNull('sent_at')
            ->whereNull('paid_at')
            ->update(['status' => 'sent']);

        DB::table('invoices')
            ->whereNotNull('paid_at')
            ->update(['status' => 'paid']);

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['sent_at']);
            $table->dropIndex(['paid_at']);
            $table->dropColumn(['sent_at', 'paid_at']);
        });
    }
};
