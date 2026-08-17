<?php

return [
    'register_routes' => env('INVOICES_REGISTER_ROUTES', true),
    'route_prefix' => env('INVOICES_ROUTE_PREFIX', 'api'),
    'route_middleware' => ['api', 'auth:sanctum'],
    'invoices_table' => env('INVOICES_TABLE', 'invoices'),
    'line_items_table' => env('INVOICE_LINE_ITEMS_TABLE', 'invoice_line_items'),
    'number_prefix' => env('INVOICE_NUMBER_PREFIX', 'INV-'),
    'number_length' => (int) env('INVOICE_NUMBER_LENGTH', 5),

    'estimates_table' => env('ESTIMATES_TABLE', 'estimates'),
    'estimate_line_items_table' => env('ESTIMATE_LINE_ITEMS_TABLE', 'estimate_line_items'),
    'estimate_cost_items_table' => env('ESTIMATE_COST_ITEMS_TABLE', 'estimate_cost_items'),
    'estimate_number_prefix' => env('ESTIMATE_NUMBER_PREFIX', 'EST-'),
];
