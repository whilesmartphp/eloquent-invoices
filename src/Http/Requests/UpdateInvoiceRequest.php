<?php

namespace Whilesmart\Invoices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerRequest;

class UpdateInvoiceRequest extends FormRequest
{
    use AuthorizesOwnerRequest;

    public function authorize(): bool
    {
        return $this->authorizeOwnerOfBoundModel('invoice');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'status' => ['nullable', 'string'],
            'issue_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'tax_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'line_items' => ['sometimes', 'array'],
            'line_items.*.description' => ['nullable', 'string'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_price_cents' => ['nullable', 'integer', 'min:0'],
            'line_items.*.unit' => ['nullable', 'string'],
            'line_items.*.position' => ['nullable', 'integer'],
            'line_items.*.metadata' => ['nullable', 'array'],
            'line_items.*.invoiceable_type' => ['nullable', 'string'],
            'line_items.*.invoiceable_id' => ['nullable'],
        ];
    }
}
