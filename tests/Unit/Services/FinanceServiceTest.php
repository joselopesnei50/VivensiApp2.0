<?php

use App\Services\FinanceService;

it('finance service can be instantiated', function () {
    expect(new FinanceService())->toBeInstanceOf(FinanceService::class);
});

it('sanitizes brazilian currency format correctly', function () {
    $service = new FinanceService();
    $reflection = new ReflectionMethod($service, 'sanitizeCurrency');
    $reflection->setAccessible(true);

    $result = $reflection->invoke($service, ['amount' => '1.500,75'], 'amount');
    expect($result['amount'])->toBe('1500.75');
});

it('sanitizes simple value without thousands separator', function () {
    $service = new FinanceService();
    $reflection = new ReflectionMethod($service, 'sanitizeCurrency');
    $reflection->setAccessible(true);

    $result = $reflection->invoke($service, ['amount' => '250,00'], 'amount');
    expect($result['amount'])->toBe('250.00');
});
