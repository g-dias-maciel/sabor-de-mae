<?php

namespace App\Livewire;

use App\Models\Address;
use App\Models\DeliveryZone;
use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\MercadoPagoService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Checkout extends Component
{
    public array $cart = [];
    public ?int $deliveryZoneId = null;
    public string $deliveryAddress = '';
    public string $paymentMethod = 'pix';
    public ?string $errorMessage = null;
    public ?string $successMessage = null;
    public bool $showPixModal = false;
    public string $pixQrCodeBase64 = '';
    public string $pixCopyPaste = '';
    public string $pixQrCode = '';
    public bool $pixConfirmed = false;
    public ?int $orderId = null;

    // ---- Seamless Auth ----
    public string $authMode = 'login'; // 'login' | 'register'
    public string $loginEmail = '';
    public string $loginPassword = '';
    public string $registerName = '';
    public string $registerEmail = '';
    public string $registerPassword = '';
    public string $registerPhone = '';

    // ---- Structured Address ----
    public string $street = '';
    public string $number = '';
    public ?string $complement = null;
    public string $neighborhood = '';
    public string $city = 'São Paulo';
    public string $zipCode = '';
    public bool $saveAddress = true;

    // ---- Saved Addresses ----
    public ?int $selectedAddressId = null;
    public array $userAddresses = [];

    public bool $isAuthenticated = false;

    protected $listeners = [
        'cartUpdated' => 'onCartUpdated',
        'startCheckout' => 'onStartCheckout',
    ];

    public function mount(): void
    {
        $this->isAuthenticated = Auth::check();

        // Restaura carrinho da sessão (persiste entre navegações)
        if (session()->has('checkout_cart')) {
            $this->cart = session()->get('checkout_cart');
            $this->deliveryZoneId = session()->get('checkout_delivery_zone_id');
        }

        if ($this->isAuthenticated) {
            $this->loadUserAddress();
        }
    }

    protected function loadUserAddress(): void
    {
        $user = Auth::user();
        $this->userAddresses = $user?->addresses()->orderByDesc('is_default')->get()->toArray() ?? [];

        $address = $user?->defaultAddress();
        if ($address) {
            $this->selectedAddressId = $address->id;
            $this->fillAddressFields($address);
        }
    }

    /**
     * Chamado quando o usuário seleciona um endereço salvo no dropdown.
     */
    public function updatedSelectedAddressId(?int $value): void
    {
        if (!$value) {
            // Troca para modo manual (mantém campos preenchidos e reseta saveAddress)
            $this->saveAddress = true;
            return;
        }

        $address = Address::find($value);
        if ($address && $address->user_id === Auth::id()) {
            $this->fillAddressFields($address);
        }
    }

    protected function fillAddressFields(Address $address): void
    {
        $this->street = $address->street;
        $this->number = $address->number;
        $this->complement = $address->complement;
        $this->neighborhood = $address->neighborhood;
        $this->city = $address->city;
        $this->zipCode = $address->zip_code;
        $this->saveAddress = false; // já está salvo

        // Busca zona de entrega pelo bairro
        $this->lookupDeliveryZone();
    }

    protected function resetAddressFields(): void
    {
        $this->street = '';
        $this->number = '';
        $this->complement = null;
        $this->neighborhood = '';
        $this->city = 'São Paulo';
        $this->zipCode = '';
        $this->deliveryZoneId = null;
    }

    protected function lookupDeliveryZone(): void
    {
        if (empty($this->neighborhood)) {
            return;
        }
        $zone = DeliveryZone::whereRaw('LOWER(neighborhood) = ?', [strtolower($this->neighborhood)])->first();
        $this->deliveryZoneId = $zone?->id;
    }

    public function login(): void
    {
        $this->reset(['errorMessage', 'successMessage']);

        $this->validate([
            'loginEmail' => 'required|email',
            'loginPassword' => 'required|string',
        ]);

        if (Auth::attempt(['email' => $this->loginEmail, 'password' => $this->loginPassword])) {
            if (request()->hasSession()) {
                request()->session()->regenerate();
            }
            $this->isAuthenticated = true;
            $this->loadUserAddress();
        } else {
            $this->errorMessage = 'E-mail ou senha incorretos.';
        }
    }

    public function register(): void
    {
        $this->reset(['errorMessage', 'successMessage']);

        $this->validate([
            'registerName' => 'required|string|max:255',
            'registerEmail' => 'required|email|unique:users,email',
            'registerPassword' => 'required|string|min:6',
            'registerPhone' => 'nullable|string|max:20',
        ], [
            'registerEmail.unique' => 'Este e-mail já está cadastrado.',
            'registerPassword.min' => 'A senha deve ter no mínimo 6 caracteres.',
        ]);

        $user = User::create([
            'name' => $this->registerName,
            'email' => $this->registerEmail,
            'phone' => $this->registerPhone ?: null,
            'password' => $this->registerPassword,
            'is_admin' => false,
        ]);

        Auth::login($user);
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }
        $this->isAuthenticated = true;

        // Salva o endereço automaticamente se já preenchido
        if ($this->street && $this->number) {
            $this->saveAddressToUser($user);
        }
    }

    public function updatedNeighborhood(): void
    {
        $this->lookupDeliveryZone();

        // Se o usuário editou manualmente o bairro, desvincula do endereço salvo
        if ($this->selectedAddressId) {
            $address = Address::find($this->selectedAddressId);
            if ($address && $address->neighborhood !== $this->neighborhood) {
                $this->selectedAddressId = null;
                $this->saveAddress = true;
            }
        }
    }

    protected function saveAddressToUser(?User $user = null): void
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return;
        }

        $address = Address::create([
            'user_id' => $user->id,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'zip_code' => $this->zipCode,
            'is_default' => !$user->addresses()->exists(),
        ]);

        if ($this->saveAddress) {
            $address->setAsDefault();
        }

        $this->deliveryAddress = $address->fullAddress();
    }

    public function onCartUpdated(array $cart): void
    {
        $this->cart = $cart;
        $this->reset(['errorMessage', 'successMessage']);
    }

    public function onStartCheckout(array $data): void
    {
        $this->cart = $data['cart'] ?? [];
        $this->deliveryZoneId = $data['delivery_zone_id'] ?? null;
    }

    public function processCheckout(): void
    {
        $this->reset(['errorMessage', 'successMessage']);

        if (!Auth::check() && !$this->isAuthenticated) {
            $this->errorMessage = 'Faça login ou cadastre-se para continuar.';
            return;
        }

        if (empty($this->cart)) {
            $this->errorMessage = 'Seu carrinho está vazio.';
            return;
        }

        // Monta o endereço a partir dos campos estruturados
        $this->deliveryAddress = trim(sprintf(
            '%s, %s%s - %s, %s%s',
            $this->street,
            $this->number,
            $this->complement ? ' (' . $this->complement . ')' : '',
            $this->neighborhood,
            $this->city,
            $this->zipCode ? ' CEP: ' . $this->zipCode : '',
        ));

        if (empty($this->street) || empty($this->number)) {
            $this->errorMessage = 'Informe o endereço de entrega.';
            return;
        }

        try {
            $service = app(CheckoutService::class);
            $menu = Menu::ativo()->firstOrFail();

            $items = array_map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'size' => $item['size'] ?? 'M',
                    'notes' => $item['notes'] ?? null,
                    'salad_name' => $item['salad_name'] ?? null,
                    'salad_product_id' => $item['salad_product_id'] ?? null,
                ];
            }, array_values($this->cart));

            $order = $service->checkout(
                user: Auth::user(),
                menu: $menu,
                items: $items,
                deliveryZoneId: $this->deliveryZoneId,
                deliveryAddress: $this->deliveryAddress,
                paymentMethod: $this->paymentMethod,
            );

            // Salva endereço se marcado
            if ($this->saveAddress && Auth::check()) {
                $this->saveAddressToUser();
            }

            $this->orderId = $order->id;

            if ($this->paymentMethod === 'pix') {
                // Gera o PIX via Mercado Pago
                $mpService = app(MercadoPagoService::class);
                $result = $mpService->createPixPayment($order);

                if ($result['success']) {
                    $this->pixQrCodeBase64 = $result['qr_code_base64'] ?? '';
                    $this->pixQrCode = $result['qr_code'] ?? '';
                    $this->pixCopyPaste = $result['qr_code'] ?? '';
                    $this->showPixModal = true;
                } else {
                    $this->errorMessage = $result['message'];
                    return;
                }
            } else {
                $this->successMessage = 'Pedido realizado com sucesso! Pagamento na entrega.';
                $this->clearCart();
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Verifica o status do pagamento via polling.
     * Chamado automaticamente pelo wire:poll.
     */
    public function checkPaymentStatus(): void
    {
        if (!$this->orderId || !$this->showPixModal) {
            return;
        }

        $order = Order::find($this->orderId);

        if ($order && $order->payment_status === 'pago') {
            $this->pixConfirmed = true;
            $this->showPixModal = false;
            $this->successMessage = 'Pagamento confirmado! Pedido #' . $this->orderId . ' realizado com sucesso! ✅';
            $this->clearCart();
        }
    }

    public function closePixModal(): void
    {
        $this->showPixModal = false;
        $this->successMessage = 'Pedido #' . $this->orderId . ' realizado com sucesso! Aguardando confirmação do pagamento.';
        $this->clearCart();
    }

    protected function clearCart(): void
    {
        $this->cart = [];
        session()->forget(['checkout_cart', 'checkout_delivery_zone_id']);
        $this->dispatch('cartUpdated', cart: []);
    }

    /**
     * Lista os bairros disponíveis para o select.
     */
    public function getNeighborhoodOptionsProperty(): array
    {
        return DeliveryZone::pluck('neighborhood')->toArray();
    }

    public function render()
    {
        return view('livewire.checkout', [
            'neighborhoodOptions' => $this->neighborhoodOptions,
        ]);
    }
}
