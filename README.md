# whilesmart/eloquent-invoices

Polymorphic invoice management with line items for Laravel applications. Designed to pair with `whilesmart/eloquent-customers`.

## Install

```bash
composer require whilesmart/eloquent-invoices
php artisan migrate
```

The customers package is a hard dependency — composer will pull it in automatically.

## Use

Add `HasInvoices` to any model that should own invoices (Workspace, Organization, User, etc.):

```php
use Whilesmart\Invoices\Traits\HasInvoices;

class Workspace extends Model
{
    use HasInvoices;
}
```

The trait gives you a `morphMany` relation:

```php
$invoice = $workspace->invoices()->create([
    'customer_id' => $customer->id,
    'number' => 'INV-2026-0001',
    'issue_date' => today(),
    'due_date' => today()->addDays(30),
    'currency' => 'USD',
]);

$invoice->lineItems()->create([
    'description' => 'Roofing labour',
    'quantity' => 8,
    'unit' => 'hour',
    'unit_price_cents' => 7500,
]);

$invoice->recalculate()->save();
```

`recalculate()` reads the line items and computes `subtotal_cents`, then applies `discount_cents` and `tax_cents` to set `total_cents`. `balanceCents()` returns the unpaid remainder (`total - amount_paid`).

## Endpoints

| Verb | Path | Notes |
|---|---|---|
| `GET` | `/api/invoices` | List, filter by `owner_type`+`owner_id`, `status`, `customer_id` |
| `POST` | `/api/invoices` | Create (with optional nested `line_items`) |
| `GET` | `/api/invoices/{id}` | Show with customer + line items |
| `PUT` | `/api/invoices/{id}` | Update header fields |
| `DELETE` | `/api/invoices/{id}` | Soft delete |
| `POST` | `/api/invoices/{id}/send` | Mark as sent, set `sent_at` |
| `POST` | `/api/invoices/{id}/mark-paid` | Apply payment (`amount_cents`), advance status to `partially_paid` or `paid` |
| `POST` | `/api/invoices/{id}/void` | Mark as void |

## Polymorphic line items

Line items can either be **freehand** (you supply description / quantity / unit_price_cents directly) or **bound to an invoiceable model** via a `morphTo` relation. Any model that implements `Whilesmart\Invoices\Contracts\Invoiceable` (or uses the `IsInvoiceable` trait) can be referenced.

```php
use Whilesmart\Invoices\Traits\IsInvoiceable;

class LabourRate extends Model
{
    use IsInvoiceable;
    // expose `name`, `default_unit`, `default_price_cents` and the trait does the rest
}
```

Then create an invoice line item by reference:

```php
$invoice->lineItems()->create([
    'invoiceable_type' => LabourRate::class,
    'invoiceable_id'   => $rate->id,
    'quantity'         => 8,
    // description / unit / unit_price_cents are pulled from the rate and snapshotted
]);
```

Or via the API:

```json
POST /api/invoices
{
  "owner_type": "App\\Models\\Workspace",
  "owner_id": 12,
  "number": "INV-2026-0001",
  "issue_date": "2026-04-07",
  "line_items": [
    { "invoiceable_type": "App\\Models\\LabourRate", "invoiceable_id": 5, "quantity": 8 },
    { "description": "Site cleanup", "quantity": 1, "unit_price_cents": 12000 }
  ]
}
```

Snapshot semantics: when a line item is created from an invoiceable, description / unit / price are **copied** at create time. Later edits to the source model never mutate historical invoices.

Pair with `whilesmart/eloquent-products` for a generic SKU catalog as the first concrete `Invoiceable`.

## Status enum

`Whilesmart\Invoices\Enums\InvoiceStatus`: `draft`, `sent`, `partially_paid`, `paid`, `overdue`, `void`.

## Schema

`invoices` table:

| column | type |
|---|---|
| id | bigint |
| owner_type / owner_id | morphs |
| customer_id | foreignId nullable → customers |
| number | unique string |
| status | string (enum) |
| issue_date / due_date / sent_at / paid_at | date |
| currency | char(3) |
| subtotal_cents / discount_cents / tax_cents / total_cents / amount_paid_cents | bigint |
| notes / terms | text |
| metadata | json |
| timestamps + soft deletes | |

`invoice_line_items` table:

| column | type |
|---|---|
| id | bigint |
| invoice_id | foreignId → invoices |
| position | unsigned int |
| description | string |
| quantity | decimal(12,4) |
| unit | nullable string |
| unit_price_cents / amount_cents | bigint |
| metadata | json |
| timestamps | |

`amount_cents` is auto-computed from `quantity * unit_price_cents` on save.

## Config

Publish with `php artisan vendor:publish --tag=invoices-config`. Override `register_routes`, `route_prefix`, `route_middleware`, table names, and `number_prefix` via env or the published config file.
