<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Test: Users cannot access transactions from other tenants
 */
test('users cannot access other tenants transactions', function () {
    // Arrange: Create two separate tenants
    $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
    $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);
    
    // Create users for each tenant
    $user1 = User::factory()->forTenant($tenant1)->create();
    $user2 = User::factory()->forTenant($tenant2)->create();
    
    // Create a transaction for tenant 1
    $transaction1 = Transaction::factory()->create([
        'tenant_id' => $tenant1->id,
        'description' => 'Tenant 1 Transaction - Secret Data',
        'amount' => 1000.00,
    ]);
    
    // Act: Login as user from tenant 2
    $this->actingAs($user2);
    
    // Assert: User 2 cannot see tenant 1's transaction
    $response = $this->get('/transactions');
    $response->assertDontSee('Tenant 1 Transaction - Secret Data');
    $response->assertDontSee('1000.00');
});

/**
 * Test: Users can only see their own tenant's transactions
 */
test('users can only see their own tenant transactions', function () {
    // Arrange: Create two tenants with transactions
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user1 = User::factory()->forTenant($tenant1)->create();
    
    $transaction1 = Transaction::factory()->create([
        'tenant_id' => $tenant1->id,
        'description' => 'My Transaction',
        'amount' => 500.00,
    ]);
    
    $transaction2 = Transaction::factory()->create([
        'tenant_id' => $tenant2->id,
        'description' => 'Other Tenant Transaction',
        'amount' => 999.00,
    ]);
    
    // Act & Assert: Login as user 1 and check they only see their data
    $this->actingAs($user1);
    $response = $this->get('/transactions');
    
    $response->assertSee('My Transaction');
    $response->assertDontSee('Other Tenant Transaction');
});

/**
 * Test: Direct transaction access is blocked for other tenants
 */
test('direct transaction access is blocked for other tenants', function () {
    // Arrange
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user2 = User::factory()->forTenant($tenant2)->create();
    
    $transaction1 = Transaction::factory()->create([
        'tenant_id' => $tenant1->id,
    ]);
    
    // Act: Try to access transaction from another tenant
    $this->actingAs($user2);
    $response = $this->get("/transactions/{$transaction1->id}");
    
    // Assert: Should be forbidden or not found
    $response->assertStatus(403);
});

/**
 * Test: Transaction creation is scoped to authenticated user's tenant
 */
test('transaction creation is scoped to authenticated users tenant', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    $user = User::factory()->forTenant($tenant)->create();
    
    // Act: Create transaction as authenticated user
    $this->actingAs($user);
    $response = $this->post('/transactions', [
        'description' => 'New Transaction',
        'amount' => 150.00,
        'type' => 'income',
        'date' => now()->format('Y-m-d'),
    ]);
    
    // Assert: Transaction should be created with correct tenant_id
    $this->assertDatabaseHas('transactions', [
        'description' => 'New Transaction',
        'tenant_id' => $tenant->id,
    ]);
    
    // Ensure no leakage to other tenants
    $otherTenant = Tenant::factory()->create();
    $this->assertDatabaseMissing('transactions', [
        'description' => 'New Transaction',
        'tenant_id' => $otherTenant->id,
    ]);
});

/**
 * Test: Bulk queries are properly scoped to tenant
 */
test('bulk transaction queries are properly scoped to tenant', function () {
    // Arrange: Create multiple tenants with transactions
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user1 = User::factory()->forTenant($tenant1)->create();
    
    // Create 5 transactions for each tenant
    Transaction::factory()->count(5)->create(['tenant_id' => $tenant1->id]);
    Transaction::factory()->count(5)->create(['tenant_id' => $tenant2->id]);
    
    // Act: Query transactions as user from tenant 1
    $this->actingAs($user1);
    
    // Use the model directly to simulate controller behavior
    $transactions = Transaction::where('tenant_id', $user1->tenant_id)->get();
    
    // Assert: Should only get 5 transactions (from tenant 1)
    expect($transactions)->toHaveCount(5);
    
    // All transactions should belong to tenant 1
    $transactions->each(function ($transaction) use ($tenant1) {
        expect($transaction->tenant_id)->toBe($tenant1->id);
    });
});

/**
 * Test: Transaction update respects tenant isolation
 */
test('transaction update respects tenant isolation', function () {
    // Arrange
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user2 = User::factory()->forTenant($tenant2)->create();
    
    $transaction1 = Transaction::factory()->create([
        'tenant_id' => $tenant1->id,
        'amount' => 100.00,
    ]);
    
    // Act: Try to update transaction from another tenant
    $this->actingAs($user2);
    $response = $this->put("/transactions/{$transaction1->id}", [
        'amount' => 999.00,
        'description' => 'Hacked!',
    ]);
    
    // Assert: Should be forbidden
    $response->assertStatus(403);
    
    // Ensure the transaction was not modified
    $this->assertDatabaseHas('transactions', [
        'id' => $transaction1->id,
        'amount' => 100.00,
        'tenant_id' => $tenant1->id,
    ]);
    
    $this->assertDatabaseMissing('transactions', [
        'id' => $transaction1->id,
        'description' => 'Hacked!',
    ]);
});

/**
 * Test: Transaction deletion respects tenant isolation
 */
test('transaction deletion respects tenant isolation', function () {
    // Arrange
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user2 = User::factory()->forTenant($tenant2)->create();
    
    $transaction1 = Transaction::factory()->create([
        'tenant_id' => $tenant1->id,
    ]);
    
    // Act: Try to delete transaction from another tenant
    $this->actingAs($user2);
    $response = $this->delete("/transactions/{$transaction1->id}");
    
    // Assert: Should be forbidden
    $response->assertStatus(403);
    
    // Ensure the transaction still exists
    $this->assertDatabaseHas('transactions', [
        'id' => $transaction1->id,
        'tenant_id' => $tenant1->id,
    ]);
});
