<?php

use App\Models\User;

it('isSuperAdmin returns true for super_admin role', function () {
    $user = new User(['role' => 'super_admin']);
    expect($user->isSuperAdmin())->toBeTrue();
});

it('isSuperAdmin returns false for all other roles', function () {
    foreach (['ngo', 'manager', 'common', 'employee'] as $role) {
        $user = new User(['role' => $role]);
        expect($user->isSuperAdmin())->toBeFalse("falhou para role: {$role}");
    }
});

it('isManager returns true only for manager role', function () {
    expect((new User(['role' => 'manager']))->isManager())->toBeTrue();
    expect((new User(['role' => 'ngo']))->isManager())->toBeFalse();
    expect((new User(['role' => 'super_admin']))->isManager())->toBeFalse();
});

it('isNgo returns true for ngo role', function () {
    expect((new User(['role' => 'ngo']))->isNgo())->toBeTrue();
    expect((new User(['role' => 'manager']))->isNgo())->toBeFalse();
});

it('hasCommonPanel returns true for common role', function () {
    expect((new User(['role' => 'common']))->hasCommonPanel())->toBeTrue();
    expect((new User(['role' => 'manager']))->hasCommonPanel())->toBeFalse();
});
