<?php

return [
    'register_routes' => env('INVOICES_REGISTER_ROUTES', true),
    'route_prefix' => env('INVOICES_ROUTE_PREFIX', 'api'),
    'route_middleware' => ['api', 'auth:sanctum'],
    'invoices_table' => env('INVOICES_TABLE', 'invoices'),
    'line_items_table' => env('INVOICE_LINE_ITEMS_TABLE', 'invoice_line_items'),
    'number_prefix' => env('INVOICE_NUMBER_PREFIX', 'INV-'),
];
