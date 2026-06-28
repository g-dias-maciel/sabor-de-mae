<?php

use App\Models\Order;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Support\Facades\Http;

test('mercado pago service cria pagamento pix com sucesso', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id' => 987654321,
            'status' => 'pending',
            'point_of_interaction' => [
                'transaction_data' => [
                    'qr_code' => '00020101021226860014br.gov.bcb.pix...',
                    'qr_code_base64' => 'iVBORw0KGgoAAAANSUhEUgAAA...',
                ],
            ],
        ], 201),
    ]);

    $service = new MercadoPagoService;
    $order = Order::factory()->create([
        'user_id' => User::factory()->create()->id,
        'total' => 75.90,
        'payment_method' => 'pix',
        'payment_status' => 'pendente',
    ]);

    $result = $service->createPixPayment($order);

    expect($result['success'])->toBeTrue()
        ->and($result['transaction_id'])->toBe(987654321)
        ->and($result['qr_code_base64'])->toContain('iVBORw0KGgo')
        ->and($result['qr_code'])->toContain('00020101');

    // Verifica que o pedido foi atualizado
    expect($order->fresh()->gateway_transaction_id)->toBe('987654321');
});

test('mercado pago service retorna erro quando API falha', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'message' => 'Invalid token',
            'error' => 'invalid_token',
        ], 401),
    ]);

    $service = new MercadoPagoService;
    $order = Order::factory()->create([
        'user_id' => User::factory()->create()->id,
        'total' => 50.00,
        'payment_method' => 'pix',
        'payment_status' => 'pendente',
    ]);

    $result = $service->createPixPayment($order);

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Falha ao gerar');
});
