<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceSyncService
{
    public static function sync(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $invoice = $invoice->fresh(['hours']);
            $hours = $invoice->hours->sortBy('date')->values();
            [$billingPeriodStart, $billingPeriodEnd] = InvoiceBillingPeriodService::fromHours(
                $hours,
                $invoice->date->copy()
            );

            $invoice->forceFill([
                'amount' => $hours->sum(fn ($hour) => $hour->hours * $hour->rate),
                'billing_period_start' => $billingPeriodStart->toDateString(),
                'billing_period_end' => $billingPeriodEnd->toDateString(),
                'reference' => InvoiceBillingPeriodService::buildReference($billingPeriodStart, $billingPeriodEnd),
            ])->save();

            return $invoice->fresh(['hours']);
        });
    }
}
