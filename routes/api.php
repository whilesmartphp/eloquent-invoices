<?php

use Illuminate\Support\Facades\Route;
use Whilesmart\Invoices\Http\Controllers\InvoiceController;

Route::apiResource('invoices', InvoiceController::class);
Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid']);
Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void']);
