<?php

use App\Http\Controllers\InvoiceViewController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'manage');
Route::redirect('/login', 'manage');

Route::middleware(['auth'])->group(function () {
    Route::get('invoices/{invoice}', InvoiceViewController::class)
        ->name('invoice.view');
});
