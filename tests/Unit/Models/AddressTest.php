<?php

use App\Models\Address;
use App\Models\User;

test('address belongs to user', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $user->id]);

    expect($address->user)->toBeInstanceOf(User::class)
        ->and($address->user->id)->toBe($user->id);
});

test('address fillable contains all fields', function () {
    $address = new Address();

    expect($address->getFillable())->toContain(
        'street', 'number', 'complement', 'neighborhood', 'city', 'zip_code', 'is_default'
    );
});

test('address casts is_default as boolean', function () {
    $address = Address::factory()->create(['is_default' => true]);

    expect($address->is_default)->toBeBool()
        ->and($address->is_default)->toBeTrue();
});

test('address setAsDefault updates other addresses', function () {
    $user = User::factory()->create();
    $oldDefault = Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $newDefault = Address::factory()->create(['user_id' => $user->id, 'is_default' => false]);

    $newDefault->setAsDefault();

    expect($newDefault->fresh()->is_default)->toBeTrue()
        ->and($oldDefault->fresh()->is_default)->toBeFalse();
});

test('user has many addresses', function () {
    $user = User::factory()->create();
    Address::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->addresses)->toHaveCount(2)
        ->and($user->addresses->first())->toBeInstanceOf(Address::class);
});

test('user defaultAddress retorna endereco padrao', function () {
    $user = User::factory()->create();
    Address::factory()->notDefault()->create(['user_id' => $user->id]);
    $default = Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);

    $result = $user->defaultAddress();

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($default->id);
});

test('user defaultAddress retorna null quando nao ha endereco padrao', function () {
    $user = User::factory()->create();

    expect($user->defaultAddress())->toBeNull();
});
