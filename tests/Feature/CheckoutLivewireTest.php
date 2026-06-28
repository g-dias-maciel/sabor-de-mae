<?php

use App\Livewire\Checkout;
use App\Models\DeliveryZone;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('checkout exibe erro com carrinho vazio', function () {
    $user = User::factory()->cliente()->create();
    Auth::login($user);

    Livewire::test(Checkout::class)
        ->set('cart', [])
        ->call('processCheckout')
        ->assertSee('Seu carrinho está vazio');
});

test('checkout exibe erro sem endereco de entrega', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $user = User::factory()->cliente()->create();
    Auth::login($user);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);
    $product = Product::factory()->withSinglePrice(25.00)->create();
    $menu->products()->attach($product->id);

    Livewire::test(Checkout::class)
        ->set('cart', [
            $product->id . ':M' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => 25.00,
                'quantity' => 1,
                'size' => 'M',
            ]
        ])
        ->set('street', '')
        ->set('number', '')
        ->call('processCheckout')
        ->assertSee('Informe o endereço de entrega');

    Carbon\Carbon::setTestNow();
    Auth::logout();
});

test('checkout processa pedido com sucesso via pix', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id' => 555111222,
            'status' => 'pending',
            'point_of_interaction' => [
                'transaction_data' => [
                    'qr_code' => '00020101021226860014br.gov.bcb.pix...',
                    'qr_code_base64' => 'iVBORw0KGgoTESTE...',
                ],
            ],
        ], 201),
    ]);

    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $user = User::factory()->cliente()->create();
    Auth::login($user);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);
    $product = Product::factory()->withSinglePrice(20.00)->create();
    $menu->products()->attach($product->id);

    $zone = DeliveryZone::factory()->create(['fee' => 5.00]);

    Livewire::test(Checkout::class)
        ->set('cart', [
            $product->id . ':M' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => 20.00,
                'quantity' => 2,
                'size' => 'M',
            ]
        ])
        ->set('street', 'Rua Teste')
        ->set('number', '123')
        ->set('neighborhood', $zone->neighborhood)
        ->set('paymentMethod', 'pix')
        ->call('processCheckout')
        ->assertSet('showPixModal', true)
        ->assertSet('pixQrCodeBase64', 'iVBORw0KGgoTESTE...')
        ->assertSet('pixCopyPaste', '00020101021226860014br.gov.bcb.pix...')
        ->assertSet('orderId', fn ($id) => !is_null($id));

    Carbon\Carbon::setTestNow();
    Auth::logout();
});

test('checkout processa pedido com sucesso via dinheiro', function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $user = User::factory()->cliente()->create();
    Auth::login($user);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);
    $product = Product::factory()->withSinglePrice(20.00)->create();
    $menu->products()->attach($product->id);

    Livewire::test(Checkout::class)
        ->set('cart', [
            $product->id . ':M' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => 20.00,
                'quantity' => 1,
                'size' => 'M',
            ]
        ])
        ->set('street', 'Rua A')
        ->set('number', '456')
        ->set('neighborhood', 'Centro')
        ->set('paymentMethod', 'dinheiro')
        ->call('processCheckout')
        ->assertSee('Pedido realizado com sucesso');

    Carbon\Carbon::setTestNow();
    Auth::logout();
});

test('checkout pix exibe qr code e copia-cola no modal', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id' => 777888999,
            'status' => 'pending',
            'point_of_interaction' => [
                'transaction_data' => [
                    'qr_code' => '00020101021226860014br.gov.bcb.pix...',
                    'qr_code_base64' => 'iVBORw0KGgoAAAANSUhEUg...',
                ],
            ],
        ], 201),
    ]);

    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $user = User::factory()->cliente()->create();
    Auth::login($user);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);
    $product = Product::factory()->withSinglePrice(30.00)->create();
    $menu->products()->attach($product->id);

    $zone = DeliveryZone::factory()->create(['fee' => 5.00]);

    $component = Livewire::test(Checkout::class)
        ->set('cart', [
            $product->id . ':M' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => 30.00,
                'quantity' => 1,
                'size' => 'M',
            ]
        ])
        ->set('street', 'Rua Teste')
        ->set('number', '100')
        ->set('neighborhood', $zone->neighborhood)
        ->set('paymentMethod', 'pix')
        ->call('processCheckout');

    // Verifica que o modal PIX está aberto com os dados reais
    $component->assertSet('showPixModal', true);
    $component->assertSet('pixCopyPaste', '00020101021226860014br.gov.bcb.pix...');
    $component->assertSee('data:image/png;base64,iVBORw0KGgoAAAANSUhEUg...');
    $component->assertSee('Copiar Código Pix');

    Carbon\Carbon::setTestNow();
    Auth::logout();
});

test('checkout pix polling confirma pagamento automaticamente', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id' => 111222333,
            'status' => 'pending',
            'point_of_interaction' => [
                'transaction_data' => [
                    'qr_code' => '00020101021226860014br.gov.bcb.pix...',
                    'qr_code_base64' => 'iVBORw0KGgoAAAANSUhEUg...',
                ],
            ],
        ], 201),
    ]);

    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $user = User::factory()->cliente()->create();
    Auth::login($user);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);
    $product = Product::factory()->withSinglePrice(30.00)->create();
    $menu->products()->attach($product->id);

    $zone = DeliveryZone::factory()->create(['fee' => 5.00]);

    $checkout = Livewire::test(Checkout::class);

    $checkout
        ->set('cart', [
            $product->id . ':M' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => 30.00,
                'quantity' => 1,
                'size' => 'M',
            ]
        ])
        ->set('street', 'Rua X')
        ->set('number', '99')
        ->set('neighborhood', $zone->neighborhood)
        ->set('paymentMethod', 'pix')
        ->call('processCheckout');

    // O modal deve estar aberto
    $checkout->assertSet('showPixModal', true);

    // Simula o webhook ter confirmado o pagamento
    $orderId = $checkout->get('orderId');
    Order::where('id', $orderId)->update(['payment_status' => 'pago']);

    // Polling verifica e fecha o modal automaticamente
    $checkout->call('checkPaymentStatus');

    $checkout->assertSet('showPixModal', false);
    $checkout->assertSet('pixConfirmed', true);
    $checkout->assertSee('Pagamento confirmado');

    Carbon\Carbon::setTestNow();
    Auth::logout();
});

test('checkout pix falha graciosamente quando MercadoPago esta indisponivel', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'message' => 'Service Unavailable',
        ], 503),
    ]);

    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3));

    $user = User::factory()->cliente()->create();
    Auth::login($user);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek(),
        'end_date' => now()->endOfWeek(),
    ]);
    $product = Product::factory()->withSinglePrice(20.00)->create();
    $menu->products()->attach($product->id);

    $zone = DeliveryZone::factory()->create(['fee' => 5.00]);

    Livewire::test(Checkout::class)
        ->set('cart', [
            $product->id . ':M' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => 20.00,
                'quantity' => 1,
                'size' => 'M',
            ]
        ])
        ->set('street', 'Rua A')
        ->set('number', '1')
        ->set('neighborhood', $zone->neighborhood)
        ->set('paymentMethod', 'pix')
        ->call('processCheckout')
        ->assertSet('showPixModal', false)
        ->assertSee('Falha ao gerar pagamento PIX');

    Carbon\Carbon::setTestNow();
    Auth::logout();
});
