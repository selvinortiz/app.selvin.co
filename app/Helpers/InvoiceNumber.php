<?php

namespace App\Helpers;

use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceNumber
{
    public static function generate(Client $client, ?Carbon $date = null): string
    {
        $date = $date ?? now();
        return $client->code . $date->format('Ymd');
    }

    public static function generateUnique(Client $client, ?Carbon $date = null, ?int $ignoreInvoiceId = null): string
    {
        $baseNumber = static::generate($client, $date);
        $candidate = $baseNumber;
        $suffix = 2;

        while (static::exists($candidate, $ignoreInvoiceId)) {
            $candidate = "{$baseNumber}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    public static function extractDate(string $number): ?Carbon
    {
        if (preg_match('/(\d{8})(?:-\d+)?$/', $number, $matches)) {
            return Carbon::createFromFormat('Ymd', $matches[1]);
        }

        return null;
    }

    public static function extractClientCode(string $number): ?string
    {
        return preg_replace('/\d{8}(?:-\d+)?$/', '', $number) ?: null;
    }

    protected static function exists(string $number, ?int $ignoreInvoiceId = null): bool
    {
        return Invoice::query()
            ->where('number', $number)
            ->when($ignoreInvoiceId, fn ($query) => $query->whereKeyNot($ignoreInvoiceId))
            ->exists();
    }
}
