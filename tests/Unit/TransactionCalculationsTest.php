<?php

use App\Models\Transaction;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Test: Transaction amount calculation is accurate
 */
test('transaction amount calculation is accurate', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    
    $transaction1 = Transaction::factory()->create([
        'tenant_id' => $tenant->id,
        'amount' => 100.50,
        'type' => 'income',
    ]);
    
    $transaction2 = Transaction::factory()->create([
        'tenant_id' => $tenant->id,
        'amount' => 50.25,
        'type' => 'expense',
    ]);
    
    // Act: Calculate balance
    $totalIncome = Transaction::where('tenant_id', $tenant->id)
        ->where('type', 'income')
        ->sum('amount');
    
    $totalExpense = Transaction::where('tenant_id', $tenant->id)
        ->where('type', 'expense')
        ->sum('amount');
    
    $balance = $totalIncome - $totalExpense;
    
    // Assert
    expect($totalIncome)->toBe(100.50);
    expect($totalExpense)->toBe(50.25);
    expect($balance)->toBe(50.25);
});

/**
 * Test: Multiple transactions sum correctly
 */
test('multiple transactions sum correctly', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    
    // Create multiple income transactions
    Transaction::factory()->count(3)->create([
        'tenant_id' => $tenant->id,
        'amount' => 100.00,
        'type' => 'income',
    ]);
    
    // Create multiple expense transactions
    Transaction::factory()->count(2)->create([
        'tenant_id' => $tenant->id,
        'amount' => 75.00,
        'type' => 'expense',
    ]);
    
    // Act
    $totalIncome = Transaction::where('tenant_id', $tenant->id)
        ->where('type', 'income')
        ->sum('amount');
    
    $totalExpense = Transaction::where('tenant_id', $tenant->id)
        ->where('type', 'expense')
        ->sum('amount');
    
    // Assert
    expect($totalIncome)->toBe(300.00); // 3 × 100
    expect($totalExpense)->toBe(150.00); // 2 × 75
});

/**
 * Test: Decimal precision is maintained
 */
test('decimal precision is maintained in calculations', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    
    Transaction::factory()->create([
        'tenant_id' => $tenant->id,
        'amount' => 99.99,
        'type' => 'income',
    ]);
    
    Transaction::factory()->create([
        'tenant_id' => $tenant->id,
        'amount' => 0.01,
        'type' => 'expense',
    ]);
    
    // Act
    $balance = Transaction::where('tenant_id', $tenant->id)
        ->where('type', 'income')
        ->sum('amount')
        - Transaction::where('tenant_id', $tenant->id)
        ->where('type', 'expense')
        ->sum('amount');
    
    // Assert
    expect($balance)->toBe(99.98);
});

/**
 * Test: Only approved transactions are counted in budget
 */
test('only approved transactions count towards budget calculations', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    
    // Approved transactions
    Transaction::factory()->approved()->create([
        'tenant_id' => $tenant->id,
        'amount' => 100.00,
        'type' => 'income',
    ]);
    
    // Pending transactions (should not count)
    Transaction::factory()->create([
        'tenant_id' => $tenant->id,
        'amount' => 500.00,
        'type' => 'income',
        'approval_status' => 'pending',
    ]);
    
    // Act: Calculate approved income only
    $approvedIncome = Transaction::where('tenant_id', $tenant->id)
        ->where('type', 'income')
        ->where('approval_status', 'approved')
        ->sum('amount');
    
    // Assert: Should only include the approved transaction
    expect($approvedIncome)->toBe(100.00);
});

/**
 * Test: Transaction status affects balance calculation
 */
test('only completed transactions affect balance', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    
    // Completed transaction
    Transaction::factory()->create([
        'tenant_id' => $tenant->id,
        'amount' => 100.00,
        'type' => 'income',
        'status' => 'completed',
    ]);
    
    // Pending transaction
    Transaction::factory()->create([
        'tenant_id' => $tenant->id,
        'amount' => 200.00,
        'type' => 'income',
        'status' => 'pending',
    ]);
    
    // Act
    $completedBalance = Transaction::where('tenant_id', $tenant->id)
        ->where('status', 'completed')
        ->sum('amount');
    
    // Assert
    expect($completedBalance)->toBe(100.00);
});

/**
 * Test: Category-wise totals are accurate
 */
test('category wise transaction totals are accurate', function () {
    // Arrange
    $tenant = Tenant::factory()->create();
    
    Transaction::factory()->count(2)->create([
        'tenant_id' => $tenant->id,
        'category_id' => 1,
        'amount' => 50.00,
        'type' => 'expense',
    ]);
    
    Transaction::factory()->create([
        'tenant_id' => $tenant->id,
        'category_id' => 2,
        'amount' => 75.00,
        'type' => 'expense',
    ]);
    
    // Act
    $category1Total = Transaction::where('tenant_id', $tenant->id)
        ->where('category_id', 1)
        ->sum('amount');
    
    $category2Total = Transaction::where('tenant_id', $tenant->id)
        ->where('category_id', 2)
        ->sum('amount');
    
    // Assert
    expect($category1Total)->toBe(100.00); // 2 × 50
    expect($category2Total)->toBe(75.00);
});
