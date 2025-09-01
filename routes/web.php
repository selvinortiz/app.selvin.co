<?php

use App\Http\Controllers\InvoiceViewController;
use App\Http\Controllers\SecureFileDownloadController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'manage');
Route::redirect('/login', 'manage');

Route::middleware(['auth'])->group(function () {
    Route::get('invoices/{invoice}', InvoiceViewController::class)
        ->name('invoice.view');
});

// Secure file download routes (public, no auth required)
Route::get('download/{token}', [SecureFileDownloadController::class, 'show'])
    ->name('secure-file.show');
Route::post('download/{token}', [SecureFileDownloadController::class, 'download'])
    ->name('secure-file.download');
