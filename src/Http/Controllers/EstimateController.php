<?php

namespace Whilesmart\Invoices\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Whilesmart\Invoices\Contracts\Invoiceable;
use Whilesmart\Invoices\Enums\EstimateStatus;
use Whilesmart\Invoices\Events\EstimateSent;
use Whilesmart\Invoices\Http\Requests\StoreEstimateRequest;
use Whilesmart\Invoices\Http\Requests\UpdateEstimateRequest;
use Whilesmart\Invoices\Http\Resources\EstimateResource;
use Whilesmart\Invoices\Http\Resources\InvoiceResource;
use Whilesmart\Invoices\Models\Estimate;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerController;

class EstimateController extends Controller
{
    use AuthorizesOwnerController;

    public function index(Request $request): JsonResponse
    {
        $query = $this->scopeAccessibleOwners(Estimate::with(['customer', 'lineItems']), $request->user());

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

        $estimates = $query->orderByDesc('issue_date')
            ->paginate((int) $request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => EstimateResource::collection($estimates)->response()->getData(true),
        ]);
    }

    public function store(StoreEstimateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lineItems = $data['line_items'] ?? [];
        $costItems = $data['cost_items'] ?? [];
        unset($data['line_items'], $data['cost_items']);

        $estimate = Estimate::create($data);
        $this->replaceItems($estimate, $lineItems, $costItems);

        return response()->json([
            'success' => true,
            'data' => new EstimateResource($estimate->fresh(['customer', 'lineItems', 'costItems'])),
        ], 201);
    }

    public function show(Estimate $estimate, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($estimate, $request->user());
        $estimate->load(['customer', 'lineItems', 'costItems']);

        return response()->json([
            'success' => true,
            'data' => new EstimateResource($estimate),
        ]);
    }

    public function update(UpdateEstimateRequest $request, Estimate $estimate): JsonResponse
    {
        if ($estimate->status === EstimateStatus::Accepted) {
            return response()->json(['message' => 'An accepted estimate cannot be edited.'], 422);
        }

        $data = $request->validated();
        $lineItems = $data['line_items'] ?? null;
        $costItems = $data['cost_items'] ?? null;
        unset($data['line_items'], $data['cost_items']);

        DB::transaction(function () use ($estimate, $data, $lineItems, $costItems) {
            $estimate->update($data);

            if (is_array($lineItems)) {
                $estimate->lineItems()->delete();
            }
            if (is_array($costItems)) {
                $estimate->costItems()->delete();
            }
            $this->replaceItems($estimate, $lineItems ?? [], $costItems ?? [], is_array($lineItems));
        });

        return response()->json([
            'success' => true,
            'data' => new EstimateResource($estimate->fresh(['customer', 'lineItems', 'costItems'])),
        ]);
    }

    public function destroy(Estimate $estimate, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($estimate, $request->user());
        $estimate->delete();

        return response()->json(['success' => true, 'message' => 'Estimate deleted.']);
    }

    public function send(Estimate $estimate, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($estimate, $request->user());
        $estimate->status = EstimateStatus::Sent;
        $estimate->sent_at = now();
        $estimate->save();

        EstimateSent::dispatch($estimate);

        return response()->json([
            'success' => true,
            'data' => new EstimateResource($estimate->fresh(['customer', 'lineItems', 'costItems'])),
        ]);
    }

    public function accept(Estimate $estimate, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($estimate, $request->user());

        if ($estimate->status === EstimateStatus::Accepted && $estimate->converted_invoice_id) {
            return response()->json(['message' => 'This estimate has already been accepted.'], 422);
        }

        $invoice = $estimate->convertToInvoice();

        return response()->json([
            'success' => true,
            'data' => [
                'estimate' => new EstimateResource($estimate->fresh(['customer', 'lineItems', 'costItems'])),
                'invoice' => new InvoiceResource($invoice->fresh(['customer', 'lineItems'])),
            ],
        ]);
    }

    public function decline(Estimate $estimate, Request $request): JsonResponse
    {
        $this->authorizeAccessTo($estimate, $request->user());
        $estimate->status = EstimateStatus::Declined;
        $estimate->save();

        return response()->json([
            'success' => true,
            'data' => new EstimateResource($estimate->fresh(['customer', 'lineItems', 'costItems'])),
        ]);
    }

    private function replaceItems(Estimate $estimate, array $lineItems, array $costItems, bool $recalcOnly = true): void
    {
        foreach ($lineItems as $i => $item) {
            $estimate->lineItems()->create($this->buildLineItemAttributes($item, $i));
        }
        foreach ($costItems as $i => $item) {
            $estimate->costItems()->create($this->buildCostItemAttributes($item, $i));
        }

        $estimate->recalculate()->save();
    }

    protected function buildLineItemAttributes(array $item, int $index): array
    {
        $snapshot = ['description' => null, 'quantity' => 1, 'unit' => null, 'unit_price_cents' => 0, 'metadata' => null];

        if (! empty($item['estimateable_type']) && ! empty($item['estimateable_id'])) {
            $class = $item['estimateable_type'];
            if (class_exists($class)) {
                $model = $class::find($item['estimateable_id']);
                if ($model instanceof Invoiceable) {
                    $snapshot = array_merge($snapshot, $model->toLineItemAttributes());
                }
            }
        }

        return [
            'estimateable_type' => $item['estimateable_type'] ?? null,
            'estimateable_id' => $item['estimateable_id'] ?? null,
            'position' => $item['position'] ?? $index,
            'description' => $item['description'] ?? $snapshot['description'] ?? '',
            'quantity' => $item['quantity'] ?? $snapshot['quantity'],
            'unit' => $item['unit'] ?? $snapshot['unit'],
            'unit_price_cents' => $item['unit_price_cents'] ?? $snapshot['unit_price_cents'],
            'metadata' => $item['metadata'] ?? $snapshot['metadata'],
        ];
    }

    protected function buildCostItemAttributes(array $item, int $index): array
    {
        return [
            'estimate_line_item_id' => $item['estimate_line_item_id'] ?? null,
            'category' => $item['category'] ?? null,
            'description' => $item['description'] ?? '',
            'quantity' => $item['quantity'] ?? 1,
            'unit_cost_cents' => $item['unit_cost_cents'] ?? 0,
            'notes' => $item['notes'] ?? null,
            'metadata' => $item['metadata'] ?? null,
        ];
    }
}
