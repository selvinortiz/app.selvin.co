<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\View\View;

class InvoiceViewController extends Controller
{
    public function __invoke(Invoice $invoice): View
    {
        return view('invoices.show', [
            'invoice' => $invoice,
            'print' => request()->boolean('print', false),
        ]);
    }
}
