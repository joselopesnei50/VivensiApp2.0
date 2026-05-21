<?php

use App\Models\User;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Services\FinanceService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('employee expense requires approval', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $employee = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'employee']);

    $service = new FinanceService();
    $transaction = $service->createTransaction([
        'description' => 'Material de escritório',
        'amount'      => '150.00',
        'date'        => now()->toDateString(),
        'type'        => 'expense',
    ], $employee);

    expect($transaction->approval_status)->toBe('pending')
        ->and($transaction->status)->toBe('pending');
});

it('manager expense is approved automatically', function () {
    $tenant  = Tenant::factory()->create(['subscription_status' => 'active']);
    $manager = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $service = new FinanceService();
    $transaction = $service->createTransaction([
        'description' => 'Almoço de equipe',
        'amount'      => '300.00',
        'date'        => now()->toDateString(),
        'type'        => 'expense',
    ], $manager);

    expect($transaction->approval_status)->toBe('approved')
        ->and($transaction->status)->toBe('paid');
});

it('income transaction is always paid immediately', function () {
    $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
    $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'ngo']);

    $service = new FinanceService();
    $transaction = $service->createTransaction([
        'description' => 'Doação recebida',
        'amount'      => '500.00',
        'date'        => now()->toDateString(),
        'type'        => 'income',
    ], $user);

    expect($transaction->status)->toBe('paid')
        ->and($transaction->approval_status)->toBe('approved');
});

it('finance service can approve a pending transaction', function () {
    $tenant   = Tenant::factory()->create(['subscription_status' => 'active']);
    $manager  = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);
    $employee = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'employee']);

    $transaction = Transaction::factory()->create([
        'tenant_id'       => $tenant->id,
        'type'            => 'expense',
        'status'          => 'pending',
        'approval_status' => 'pending',
    ]);

    $service = new FinanceService();
    $approved = $service->approve($transaction, $manager);

    expect($approved->approval_status)->toBe('approved')
        ->and($approved->status)->toBe('paid');
});

it('finance service can reject a pending transaction', function () {
    $tenant   = Tenant::factory()->create(['subscription_status' => 'active']);
    $manager  = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager']);

    $transaction = Transaction::factory()->create([
        'tenant_id'       => $tenant->id,
        'type'            => 'expense',
        'status'          => 'pending',
        'approval_status' => 'pending',
    ]);

    $service = new FinanceService();
    $rejected = $service->reject($transaction, $manager, 'Fora do orçamento');

    expect($rejected->approval_status)->toBe('rejected')
        ->and($rejected->status)->toBe('canceled');
});
