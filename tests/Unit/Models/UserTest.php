<?php

use App\Models\Address;
use App\Models\User;
use App\Models\Order;

test('user has many orders', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    expect($user->orders)
        ->toHaveCount(1)
        ->and($user->orders->first())
        ->toBeInstanceOf(Order::class);
});

test('user has many addresses', function () {
    $user = User::factory()->create();
    Address::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->addresses)->toHaveCount(2)
        ->and($user->addresses->first())->toBeInstanceOf(Address::class);
});

test('user isAdmin retorna true para admin', function () {
    $user = User::factory()->admin()->create();
    expect($user->isAdmin())->toBeTrue();
});

test('user isAdmin retorna false para cliente', function () {
    $user = User::factory()->cliente()->create();
    expect($user->isAdmin())->toBeFalse();
});

test('user fillable contains all fields', function () {
    $user = new User();
    // is_admin NÃO está no fillable (proteção contra mass assignment)
    expect($user->getFillable())->toContain('phone', 'address')
        ->not()->toContain('is_admin');
});
