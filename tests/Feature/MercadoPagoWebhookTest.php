<?php

use App\Models\Order;
use App\Models\User;

test('webhook mercadopago confirma pagamento com sucesso', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'total' => 90.00,
        'payment_method' => 'pix',
        'payment_status' => 'pendente',
        'gateway_transaction_id' => '123456789',
        'pix_qr_code' => '000201010212...',
        'pix_copy_paste' => '000201010212...',
    ]);

    $payload = [
        'action' => 'payment.updated',
        'data' => [
            'id' => '123456789',
        ],
    ];

    $response = $this->postJson('/webhooks/mercadopago', $payload);

    $response->assertOk();
    expect($order->fresh()->payment_status)->toBe('pago');
});

test('webhook mercadopago retorna 200 mesmo com pedido inexistente', function () {
    $payload = [
        'action' => 'payment.updated',
        'data' => [
            'id' => '999999999',
        ],
    ];

    $response = $this->postJson('/webhooks/mercadopago', $payload);

    // Retorna 200 para que o Mercado Pago não reenvie
    $response->assertOk();
});

test('webhook mercadopago ignora payload invalido', function () {
    $response = $this->postJson('/webhooks/mercadopago', [
        'unexpected' => 'payload',
    ]);

    $response->assertOk();
});
