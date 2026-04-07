<?php

namespace Whilesmart\Invoices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Whilesmart\Invoices\Enums\InvoiceStatus;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'owner_type' => ['required', 'string'],
            'owner_id' => ['required'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'number' => ['required', 'string', 'max:60'],
            'status' => ['nullable', 'string'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'tax_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['required_with:line_items', 'string'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_price_cents' => ['nullable', 'integer', 'min:0'],
            'line_items.*.unit' => ['nullable', 'string'],
            'line_items.*.position' => ['nullable', 'integer'],
            'line_items.*.metadata' => ['nullable', 'array'],
        ];
    }
}
