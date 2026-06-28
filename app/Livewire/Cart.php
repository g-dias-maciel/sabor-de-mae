<?php

namespace App\Livewire;

use App\Models\Address;
use App\Models\DeliveryZone;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Cart extends Component
{
    public array $cart = [];
    public ?int $deliveryZoneId = null;
    public float $deliveryFee = 0;
    public array $deliveryZones = [];

    // Saved addresses for authenticated users
    public ?int $selectedAddressId = null;
    public array $userAddresses = [];

    protected $listeners = ['cartUpdated' => 'onCartUpdated'];

    public function mount(): void
    {
        $this->deliveryZones = DeliveryZone::all()->toArray();

        // Restaura carrinho da sessão (persiste entre navegações e reloads)
        if (session()->has('checkout_cart')) {
            $this->cart = session()->get('checkout_cart');
            $this->deliveryZoneId = session()->get('checkout_delivery_zone_id');
            $this->recalcularTaxa();
        }

        if (Auth::check()) {
            $this->userAddresses = Auth::user()->addresses()
                ->orderByDesc('is_default')
                ->get()
                ->toArray();

            $default = Auth::user()->defaultAddress();
            if ($default) {
                $this->selectedAddressId = $default->id;
                $this->lookupDeliveryZone($default->neighborhood);
            }
        }
    }

    public function onCartUpdated(array $cart): void
    {
        $this->cart = $cart;
        $this->recalcularTaxa();

        // Persiste na sessão para o header "🛒 Carrinho" e página de checkout
        session()->put('checkout_cart', $this->cart);
        session()->put('checkout_delivery_zone_id', $this->deliveryZoneId);
    }

    public function updatedSelectedAddressId(?int $value): void
    {
        if (!$value) {
            $this->deliveryZoneId = null;
            $this->deliveryFee = 0;
            return;
        }

        $address = Address::find($value);
        if ($address && $address->user_id === Auth::id()) {
            $this->lookupDeliveryZone($address->neighborhood);
        }
    }

    public function updatedDeliveryZoneId(int $value): void
    {
        $this->recalcularTaxa();
    }

    public function recalcularTaxa(): void
    {
        if ($this->deliveryZoneId) {
            $zone = DeliveryZone::find($this->deliveryZoneId);
            $this->deliveryFee = $zone ? (float) $zone->fee : 0;
        } else {
            $this->deliveryFee = 0;
        }
    }

    protected function lookupDeliveryZone(string $neighborhood): void
    {
        $zone = DeliveryZone::whereRaw('LOWER(neighborhood) = ?', [strtolower($neighborhood)])->first();
        $this->deliveryZoneId = $zone?->id;
        $this->deliveryFee = $zone ? (float) $zone->fee : 0;
    }

    public function getSubtotalProperty(): float
    {
        return array_reduce($this->cart, function ($total, $item) {
            return $total + ((float) $item['price'] * (int) $item['quantity']);
        }, 0);
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal + $this->deliveryFee;
    }

    public function goToCheckout(): void
    {
        // Carrinho já está na sessão (atualizado via onCartUpdated)
        $this->redirect('/checkout', navigate: true);
    }

    public function render()
    {
        return view('livewire.cart');
    }
}
