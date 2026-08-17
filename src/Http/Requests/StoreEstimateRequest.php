<?php

namespace Whilesmart\Invoices\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerRequest;

class StoreEstimateRequest extends FormRequest
{
    use AuthorizesOwnerRequest;

    public function authorize(): bool
    {
        return $this->authorizeOwnerInRequest();
    }

    public function rules(): array
    {
        return [
            'owner_type' => ['required', 'string'],
            'owner_id' => ['required'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'number' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', 'string'],
            'issue_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'discount_cents' => ['nullable', 'integer', 'min:0'],
            'tax_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],

            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['nullable', 'string'],
            'line_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.unit_price_cents' => ['nullable', 'integer', 'min:0'],
            'line_items.*.unit' => ['nullable', 'string'],
            'line_items.*.position' => ['nullable', 'integer'],
            'line_items.*.metadata' => ['nullable', 'array'],
            'line_items.*.estimateable_type' => ['nullable', 'string'],
            'line_items.*.estimateable_id' => ['nullable'],

            'cost_items' => ['nullable', 'array'],
            'cost_items.*.description' => ['nullable', 'string'],
            'cost_items.*.category' => ['nullable', 'string'],
            'cost_items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'cost_items.*.unit_cost_cents' => ['nullable', 'integer', 'min:0'],
            'cost_items.*.estimate_line_item_id' => ['nullable', 'integer'],
            'cost_items.*.notes' => ['nullable', 'string'],
            'cost_items.*.metadata' => ['nullable', 'array'],
        ];
    }
}
