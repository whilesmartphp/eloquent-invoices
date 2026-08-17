<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\HostWorkspace;
use Tests\TestCase;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class EstimateAuthorizationTest extends TestCase
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

    private function estimate(HostWorkspace $ws)
    {
        return $ws->estimates()->create(['status' => 'draft', 'issue_date' => '2026-08-17', 'currency' => 'USD']);
    }

    #[Test]
    public function store_is_forbidden_when_authorizer_denies(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);

        $this->postJson('/api/estimates', [
            'owner_type' => HostWorkspace::class,
            'owner_id' => $ws->id,
            'issue_date' => '2026-08-17',
        ])->assertForbidden();
    }

    #[Test]
    public function show_update_destroy_and_actions_are_forbidden_when_authorizer_denies(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $estimate = $this->estimate($ws);

        $this->getJson("/api/estimates/{$estimate->id}")->assertForbidden();
        $this->putJson("/api/estimates/{$estimate->id}", ['notes' => 'changed'])->assertForbidden();
        $this->postJson("/api/estimates/{$estimate->id}/send")->assertForbidden();
        $this->postJson("/api/estimates/{$estimate->id}/accept")->assertForbidden();
        $this->postJson("/api/estimates/{$estimate->id}/decline")->assertForbidden();
        $this->deleteJson("/api/estimates/{$estimate->id}")->assertForbidden();

        $this->assertNull($estimate->fresh()->notes);
        $this->assertSame('draft', $estimate->fresh()->status->value);
    }

    #[Test]
    public function index_applies_scope_from_authorizer(): void
    {
        $ws = HostWorkspace::create(['name' => 'Acme']);
        $this->estimate($ws);

        $this->getJson('/api/estimates')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 0);
    }
}
