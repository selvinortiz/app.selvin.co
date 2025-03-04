<?php

use App\Http\Controllers\InvoiceViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('invoices/{invoice}', InvoiceViewController::class)
        ->name('invoice.view');
});
