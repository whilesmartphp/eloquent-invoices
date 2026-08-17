<?php

use Illuminate\Support\Facades\Route;
use Whilesmart\Invoices\Http\Controllers\EstimateController;
use Whilesmart\Invoices\Http\Controllers\InvoiceController;

Route::apiResource('invoices', InvoiceController::class);
Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid']);
Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void']);

Route::apiResource('estimates', EstimateController::class);
Route::post('estimates/{estimate}/send', [EstimateController::class, 'send']);
Route::post('estimates/{estimate}/accept', [EstimateController::class, 'accept']);
Route::post('estimates/{estimate}/decline', [EstimateController::class, 'decline']);
