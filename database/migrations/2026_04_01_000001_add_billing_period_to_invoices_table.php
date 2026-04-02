<?php

use Carbon\Carbon;
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
            $table->date('billing_period_start')->nullable()->after('date');
            $table->date('billing_period_end')->nullable()->after('billing_period_start');
            $table->index('billing_period_start');
            $table->index('billing_period_end');
        });

        DB::table('invoices')
            ->select('invoices.id', 'invoices.date')
            ->orderBy('invoices.id')
            ->chunkById(100, function ($invoices): void {
                $hourWindows = DB::table('hours')
                    ->selectRaw('invoice_id, MIN(date) as min_date, MAX(date) as max_date')
                    ->whereIn('invoice_id', $invoices->pluck('id'))
                    ->groupBy('invoice_id')
                    ->get()
                    ->keyBy('invoice_id');

                foreach ($invoices as $invoice) {
                    $window = $hourWindows->get($invoice->id);
                    $fallbackMonth = Carbon::parse($invoice->date)->startOfMonth();
                    $start = $window?->min_date
                        ? Carbon::parse($window->min_date)->startOfMonth()
                        : $fallbackMonth->copy();
                    $end = $window?->max_date
                        ? Carbon::parse($window->max_date)->startOfMonth()
                        : $fallbackMonth->copy();

                    DB::table('invoices')
                        ->where('id', $invoice->id)
                        ->update([
                            'billing_period_start' => $start->toDateString(),
                            'billing_period_end' => $end->toDateString(),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['billing_period_start']);
            $table->dropIndex(['billing_period_end']);
            $table->dropColumn(['billing_period_start', 'billing_period_end']);
        });
    }
};
