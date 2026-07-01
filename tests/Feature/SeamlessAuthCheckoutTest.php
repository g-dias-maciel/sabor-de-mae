<?php

use App\Livewire\Checkout;
use App\Models\DeliveryZone;
use App\Models\Menu;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Carbon\Carbon::setTestNow(now()->startOfWeek()->addDays(3)); // Quarta
});

afterEach(function () {
    Carbon\Carbon::setTestNow();
});

test('guest ve abas de login e cadastro no checkout', function () {
    Livewire::test(Checkout::class)
        ->assertSee('Entrar')
        ->assertSee('Criar Conta')
        ->assertSee('E-mail')
        ->assertSee('Senha');
});

test('guest pode se cadastrar durante o checkout', function () {
    Http::fake([
        'api.mercadopago.com/v1/payments' => Http::response([
            'id' => 999888777,
            'status' => 'pending',
            'point_of_interaction' => [
                'transaction_data' => [
                    'qr_code' => '00020101021226860014br.gov.bcb.pix...',
                    'qr_code_base64' => 'iVBORw0KGgoAAAANSUhEUg...',
                ],
            ],
        ], 201),
    ]);

    $menu = Menu::factory()->create([
        'status' => 'aberto',
        'start_date' => now()->startOfWeek()->addWeek(),
        'end_date' => now()->endOfWeek()->addWeek(),
    ]);
    $product = Product::factory()->withSinglePrice(25.00)->create();
    $menu->products()->attach($product->id);

    DeliveryZone::factory()->create(['neighborhood' => 'Centro', 'fee' => 5.00]);

    // O cadastro acontece em duas etapas via Livewire
    $component = Livewire::test(Checkout::class)
        ->set('authMode', 'register')
        ->set('registerName', 'Maria Silva')
        ->set('registerEmail', 'maria@teste.com')
        ->set('registerPassword', '123456')
        ->set('registerPhone', '(11) 99999-0000')
        ->call('register');

    // Após o cadastro, isAuthenticated deve ser true
    $component->assertSet('isAuthenticated', true);

    // Verifica que o usuário foi criado no banco
    $user = User::where('email', 'maria@teste.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Maria Silva')
        ->and($user->is_admin)->toBeFalse();

    // Agora faz checkout com endereço estruturado
    $component
        ->set('cart', [
            $product->id . ':M' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => 25.00,
                'quantity' => 1,
                'size' => 'M',
            ]
        ])
        ->set('street', 'Rua das Flores')
        ->set('number', '123')
        ->set('neighborhood', 'Centro')
        ->set('saveAddress', true)
        ->call('processCheckout')
        ->assertSet('showPixModal', true)
        ->assertSet('orderId', fn ($id) => !is_null($id));

    // Verifica que o endereço foi salvo
    expect($user->fresh()->addresses()->count())->toBe(1);

    Auth::logout();
});

test('guest pode fazer login durante o checkout', function () {
    User::factory()->cliente()->create([
        'email' => 'joao@teste.com',
        'password' => bcrypt('senha123'),
    ]);

    // Login via Livewire (não via HTTP — session guard protege)
    $component = Livewire::test(Checkout::class)
        ->set('loginEmail', 'joao@teste.com')
        ->set('loginPassword', 'senha123')
        ->call('login');

    $component->assertSet('isAuthenticated', true);

    Auth::logout();
});

test('login com senha errada mostra erro', function () {
    User::factory()->cliente()->create([
        'email' => 'cliente@teste.com',
        'password' => bcrypt('senha123'),
    ]);

    Livewire::test(Checkout::class)
        ->set('loginEmail', 'cliente@teste.com')
        ->set('loginPassword', 'senha-errada')
        ->call('login')
        ->assertSee('E-mail ou senha incorretos');
});

test('cadastro com email duplicado mostra erro', function () {
    User::factory()->cliente()->create(['email' => 'existente@teste.com']);

    Livewire::test(Checkout::class)
        ->set('authMode', 'register')
        ->set('registerName', 'João')
        ->set('registerEmail', 'existente@teste.com')
        ->set('registerPassword', '123456')
        ->call('register')
        ->assertHasErrors('registerEmail');
});

test('endereco e salvo automaticamente apos registro', function () {
    DeliveryZone::factory()->create(['neighborhood' => 'Vila Mariana']);

    Livewire::test(Checkout::class)
        ->set('authMode', 'register')
        ->set('registerName', 'Ana Costa')
        ->set('registerEmail', 'ana@teste.com')
        ->set('registerPassword', '123456')
        ->set('street', 'Rua Principal')
        ->set('number', '456')
        ->set('neighborhood', 'Vila Mariana')
        ->call('register');

    $user = User::where('email', 'ana@teste.com')->first();
    $address = $user->addresses()->first();

    expect($address)->not->toBeNull()
        ->and($address->street)->toBe('Rua Principal')
        ->and($address->number)->toBe('456')
        ->and($address->neighborhood)->toBe('Vila Mariana')
        ->and($address->is_default)->toBeTrue();
});

test('bairro sincroniza zona de entrega automaticamente', function () {
    $zone = DeliveryZone::factory()->create([
        'neighborhood' => 'Jardins',
        'fee' => 12.00,
    ]);

    // Livewire v4: set() dispara o hook updatedNeighborhood automaticamente
    Livewire::test(Checkout::class)
        ->set('neighborhood', 'Jardins')
        ->assertSet('deliveryZoneId', $zone->id);
});

test('bairro nao encontrado limpa zona de entrega', function () {
    // set() dispara updatedNeighborhood automaticamente no Livewire v4
    Livewire::test(Checkout::class)
        ->set('deliveryZoneId', 999)
        ->set('neighborhood', 'Bairro Inexistente')
        ->assertSet('deliveryZoneId', null);
});
