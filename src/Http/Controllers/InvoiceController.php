<?php

namespace Whilesmart\Invoices\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Whilesmart\Invoices\Enums\InvoiceStatus;
use Whilesmart\Invoices\Http\Requests\StoreInvoiceRequest;
use Whilesmart\Invoices\Http\Requests\UpdateInvoiceRequest;
use Whilesmart\Invoices\Http\Resources\InvoiceResource;
use Whilesmart\Invoices\Models\Invoice;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer', 'lineItems']);

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
            $invoice->lineItems()->create([
                'position' => $item['position'] ?? $i,
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'unit' => $item['unit'] ?? null,
                'unit_price_cents' => $item['unit_price_cents'] ?? 0,
                'metadata' => $item['metadata'] ?? null,
            ]);
        }

        $invoice->recalculate()->save();

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
        ], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['customer', 'lineItems']);

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $invoice->update($request->validated());
        $invoice->recalculate()->save();

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
        ]);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted.',
        ]);
    }

    public function send(Invoice $invoice): JsonResponse
    {
        $invoice->status = InvoiceStatus::Sent;
        $invoice->sent_at = now();
        $invoice->save();

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
        ]);
    }

    public function markPaid(Request $request, Invoice $invoice): JsonResponse
    {
        $amount = (int) $request->input('amount_cents', $invoice->balanceCents());
        $invoice->amount_paid_cents += $amount;
        $invoice->status = $invoice->balanceCents() === 0
            ? InvoiceStatus::Paid
            : InvoiceStatus::PartiallyPaid;
        if ($invoice->status === InvoiceStatus::Paid) {
            $invoice->paid_at = now();
        }
        $invoice->save();

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
        ]);
    }

    public function void(Invoice $invoice): JsonResponse
    {
        $invoice->status = InvoiceStatus::Void;
        $invoice->save();

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
        ]);
    }
}
