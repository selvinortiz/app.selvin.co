<?php

namespace App\Helpers;

use App\Models\Client;
use Carbon\Carbon;

class InvoiceNumber
{
    public static function generate(Client $client, ?Carbon $date = null): string
    {
        $date = $date ?? now();
        return $client->code . $date->format('Ymd');
    }

    public static function extractDate(string $number): ?Carbon
    {
        if (preg_match('/(\d{8})$/', $number, $matches)) {
            return Carbon::createFromFormat('Ymd', $matches[1]);
        }

        return null;
    }

    public static function extractClientCode(string $number): ?string
    {
        return preg_replace('/\d{8}$/', '', $number) ?: null;
    }
}
