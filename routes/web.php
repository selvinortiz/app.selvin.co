<?php

use App\Http\Controllers\InvoiceViewController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'manage');
Route::redirect('/login', 'manage');

Route::get('hours/{token}', PortalController::class)
    ->name('hours')
    ->where('token', '[A-Za-z0-9]{64}');

Route::middleware(['auth'])->group(function () {
    Route::get('invoices/{invoice}', InvoiceViewController::class)
        ->name('invoice.view');
});
