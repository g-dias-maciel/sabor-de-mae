<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\AuthForm;
use App\Livewire\ProductList;
use App\Livewire\Cart;
use App\Livewire\Checkout;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ShoppingList;
use App\Livewire\Admin\DeliveryReport;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\ProductManager;
use App\Livewire\CustomerOrders;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Rotas do Cliente
Route::get('/', ProductList::class)->name('cardapio');
Route::get('/checkout', Checkout::class)->name('checkout');
Route::get('/meus-pedidos', CustomerOrders::class)->name('meus-pedidos')->middleware('auth');
Route::get('/perfil', \App\Livewire\Profile::class)->name('perfil')->middleware('auth');

// Rotas do Painel Administrativo
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', Dashboard::class)->name('admin.dashboard');
    Route::get('/lista-compras', ShoppingList::class)->name('admin.shopping-list');
    Route::get('/entregas', DeliveryReport::class)->name('admin.delivery-report');
    Route::get('/cardapios', MenuManager::class)->name('admin.menus');
    Route::get('/produtos', ProductManager::class)->name('admin.products');
});

// Webhook Mercado Pago (sem CSRF — configurado em bootstrap/app.php)
Route::post('/webhooks/mercadopago', \App\Http\Controllers\MercadoPagoWebhookController::class);

// Autenticação
Route::get('/login', AuthForm::class)->name('login');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'store']);
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'destroy'])->name('logout');
