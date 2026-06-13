<?php

namespace Whilesmart\Invoices\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Whilesmart\Invoices\Contracts\Invoiceable;
use Whilesmart\Invoices\Enums\InvoiceStatus;
use Whilesmart\Invoices\Events\InvoicePaid;
use Whilesmart\Invoices\Events\InvoicePartiallyPaid;
use Whilesmart\Invoices\Events\InvoiceSent;
use Whilesmart\Invoices\Http\Requests\StoreInvoiceRequest;
use Whilesmart\Invoices\Http\Requests\UpdateInvoiceRequest;
use Whilesmart\Invoices\Http\Resources\InvoiceResource;
use Whilesmart\Invoices\Models\Invoice;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerController;
use Whilesmart\Payments\Enums\PaymentDirection;
use Whilesmart\Payments\Enums\PaymentStatus;

class InvoiceController extends Controller
{
    use AuthorizesOwnerController;

    public function index(Request $request): JsonResponse
    {
        $query = $this->scopeAccessibleOwners(Invoice::with(['customer', 'lineItems']), $request->user());

        if ($request->filled('owner_type') && $request->filled('owner_id')) {
            $query->where('owner_type', $request->input('owner_type'))
                ->where('owner_id', $request->input('owner_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        $invoices = $query->orderByDesc('issue_date')
            ->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => InvoiceResource::collection($invoices)->response()->getData(true),
        ]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lineItems = $data['line_items'] ?? [];
        unset($data['line_items']);

        $invoice = Invoice::create($data);

        foreach ($lineItems as $i => $item) {
            $invoice->lineItems()->create($this->buildLineItemAttributes($item, $i));
        }

        $invoice->recalculate()->save();

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
        ], 201);
    }

    public function show(Invoice $invoice, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($invoice, $request->user());
        $invoice->load(['customer', 'lineItems']);

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        if (in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::Void], true)) {
            return response()->json([
                'message' => 'A paid or void invoice cannot be edited.',
            ], 422);
        }

        $data = $request->validated();
        $lineItems = $data['line_items'] ?? null;
        unset($data['line_items']);

        $invoice->update($data);

        if (is_array($lineItems)) {
            $invoice->lineItems()->delete();
            foreach ($lineItems as $i => $item) {
                $invoice->lineItems()->create($this->buildLineItemAttributes($item, $i));
            }
        }

        $invoice->recalculate()->save();

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
        ]);
    }

    public function destroy(Invoice $invoice, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($invoice, $request->user());
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted.',
        ]);
    }

    public function send(Invoice $invoice, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($invoice, $request->user());
        $invoice->status = InvoiceStatus::Sent;
        $invoice->sent_at = now();
        $invoice->save();

        InvoiceSent::dispatch($invoice);

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
        ]);
    }

    public function markPaid(Request $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeAccessTo($invoice, $request->user());
        $amount = (int) $request->input('amount_cents', $invoice->balanceCents());

        $invoice->recordPayment([
            'owner_type' => $invoice->owner_type,
            'owner_id' => $invoice->owner_id,
            'amount_cents' => $amount,
            'currency' => $invoice->currency,
            'status' => PaymentStatus::Succeeded->value,
            'direction' => PaymentDirection::Inbound->value,
            'method' => $request->input('method', 'manual'),
            'gateway' => $request->input('gateway', 'manual'),
            'succeeded_at' => $request->input('succeeded_at', now()),
        ]);

        $invoice->status = $invoice->balanceCents() === 0
            ? InvoiceStatus::Paid
            : InvoiceStatus::PartiallyPaid;
        $invoice->save();

        if ($invoice->status === InvoiceStatus::Paid) {
            InvoicePaid::dispatch($invoice, $amount);
        } else {
            InvoicePartiallyPaid::dispatch($invoice, $amount);
        }

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
        ]);
    }

    public function void(Invoice $invoice, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($invoice, $request->user());
        $invoice->status = InvoiceStatus::Void;
        $invoice->save();

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
        ]);
    }

    protected function buildLineItemAttributes(array $item, int $index): array
    {
        $snapshot = [
            'description' => null,
            'quantity' => 1,
            'unit' => null,
            'unit_price_cents' => 0,
            'metadata' => null,
        ];

        if (! empty($item['invoiceable_type']) && ! empty($item['invoiceable_id'])) {
            $class = $item['invoiceable_type'];
            if (class_exists($class)) {
                $model = $class::find($item['invoiceable_id']);
                if ($model instanceof Invoiceable) {
                    $snapshot = array_merge($snapshot, $model->toLineItemAttributes());
                }
            }
        }

        return [
            'invoiceable_type' => $item['invoiceable_type'] ?? null,
            'invoiceable_id' => $item['invoiceable_id'] ?? null,
            'position' => $item['position'] ?? $index,
            'description' => $item['description'] ?? $snapshot['description'] ?? '',
            'quantity' => $item['quantity'] ?? $snapshot['quantity'],
            'unit' => $item['unit'] ?? $snapshot['unit'],
            'unit_price_cents' => $item['unit_price_cents'] ?? $snapshot['unit_price_cents'],
            'metadata' => $item['metadata'] ?? $snapshot['metadata'],
        ];
    }
}
