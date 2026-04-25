<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\HostWorkspace;
use Tests\TestCase;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class InvoiceAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(OwnerAuthorizer::class, new class implements OwnerAuthorizer
        {
            public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
            {
                return false;
            }

            public function scope(Builder $query, ?Authenticatable $user, string $ownerTypeColumn = 'owner_type', string $ownerIdColumn = 'owner_id'): Builder
            {
                return $query->whereRaw('0 = 1');
            }
        });
    }

    #[Test]
    public function store_returns_403_when_authorizer_denies(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);

        $this->postJson('/api/invoices', [
            'owner_type' => HostWorkspace::class,
            'owner_id' => $ws->id,
            'number' => 'INV-1',
            'issue_date' => '2026-04-25',
        ])->assertForbidden();
    }

    #[Test]
    public function show_returns_403_when_authorizer_denies(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'draft', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);

        $this->getJson("/api/invoices/{$invoice->id}")->assertForbidden();
    }

    #[Test]
    public function update_returns_403_when_authorizer_denies(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'draft', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);

        $this->putJson("/api/invoices/{$invoice->id}", ['notes' => 'changed'])
            ->assertForbidden();

        $this->assertNull($invoice->fresh()->notes);
    }

    #[Test]
    public function destroy_returns_403_when_authorizer_denies(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'draft', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);

        $this->deleteJson("/api/invoices/{$invoice->id}")->assertForbidden();
        $this->assertNotNull($invoice->fresh());
    }

    #[Test]
    public function send_returns_403_when_authorizer_denies(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'draft', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);

        $this->postJson("/api/invoices/{$invoice->id}/send")->assertForbidden();
        $this->assertSame('draft', $invoice->fresh()->status->value);
    }

    #[Test]
    public function mark_paid_returns_403_when_authorizer_denies(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'sent', 'issue_date' => '2026-04-25',
            'currency' => 'USD', 'total_cents' => 1000,
        ]);

        $this->postJson("/api/invoices/{$invoice->id}/mark-paid", ['amount_cents' => 1000])
            ->assertForbidden();

        $this->assertSame(0, $invoice->fresh()->amount_paid_cents);
    }

    #[Test]
    public function void_returns_403_when_authorizer_denies(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $invoice = $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'sent', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);

        $this->postJson("/api/invoices/{$invoice->id}/void")->assertForbidden();
        $this->assertSame('sent', $invoice->fresh()->status->value);
    }

    #[Test]
    public function index_applies_scope_from_authorizer(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $ws->invoices()->create([
            'number' => 'INV-1', 'status' => 'draft', 'issue_date' => '2026-04-25',
            'currency' => 'USD',
        ]);

        $response = $this->getJson('/api/invoices')->assertOk();

        $this->assertSame(0, $response->json('data.meta.total'));
    }
}
