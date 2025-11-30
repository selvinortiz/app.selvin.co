<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceViewController extends Controller
{
    public function __invoke(Invoice $invoice): View|Response
    {
        $user = auth()->user();

        if (!$user || !$user->tenants->pluck('id')->contains($invoice->tenant_id)) {
            abort(403, 'You do not have access to this invoice.');
        }

        return view('invoices.show', [
            'invoice' => $invoice,
            'print' => request()->boolean('print', false),
        ]);
    }
}
