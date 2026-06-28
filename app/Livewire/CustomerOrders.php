<?php

namespace App\Livewire;

use App\Models\Menu;
use App\Models\Order;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CustomerOrders extends Component
{
    /** @var \Illuminate\Support\Collection<int, Order> */
    public $currentOrders;
    public ?string $successMessage = null;

    // ---- PIX Modal ----
    public bool $showPixModal = false;
    public string $pixQrCodeBase64 = '';
    public string $pixCopyPaste = '';
    public ?int $pixOrderId = null;
    public bool $pixConfirmed = false;

    public function mount(): void
    {
        $this->currentOrders = collect();

        if (!Auth::check()) {
            return;
        }

        $this->loadOrders();
    }

    protected function loadOrders(): void
    {
        $user = Auth::user();
        $activeMenu = Menu::ativo()->first();

        if (!$activeMenu) {
            return;
        }

        $this->currentOrders = $user->orders()
            ->where('menu_id', $activeMenu->id)
            ->with('items.product')
            ->latest()
            ->get();
    }

    /**
     * Pedidos de menus anteriores.
     */
    public function getOrderHistoryProperty()
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }

        $activeMenuId = Menu::ativo()->first()?->id;

        return $user->orders()
            ->when($activeMenuId, fn($q) => $q->where('menu_id', '!=', $activeMenuId))
            ->with('items.product', 'menu')
            ->latest()
            ->get();
    }

    /**
     * Repete um pedido anterior, restaurando os itens no carrinho.
     */
    public function repeatOrder(int $orderId): void
    {
        $order = Auth::user()->orders()->with('items')->findOrFail($orderId);

        $cart = [];
        foreach ($order->items as $item) {
            $key = $item->product_id . ':' . ($item->size ?? 'M');
            $cart[$key] = [
                'product_id' => $item->product_id,
                'name' => $item->product?->name ?? 'Produto',
                'price' => (float) $item->price_at_purchase,
                'quantity' => $item->quantity,
                'size' => $item->size ?? 'M',
            ];
        }

        $this->dispatch('restoreCart', cart: $cart);
        $this->successMessage = 'Carrinho restaurado com os itens do pedido #' . $orderId . '!';
    }

    /**
     * Edita um pedido pendente: restaura carrinho e redireciona para checkout.
     */
    public function editOrder(int $orderId): void
    {
        $order = Auth::user()->orders()->with('items')->findOrFail($orderId);

        if ($order->payment_status !== 'pendente') {
            $this->successMessage = 'Este pedido já foi pago e não pode ser editado.';
            return;
        }

        $cart = [];
        foreach ($order->items as $item) {
            $key = $item->product_id . ':' . ($item->size ?? 'M');
            $cart[$key] = [
                'product_id' => $item->product_id,
                'name' => $item->product?->name ?? 'Produto',
                'price' => (float) $item->price_at_purchase,
                'quantity' => $item->quantity,
                'size' => $item->size ?? 'M',
            ];
        }

        session()->put('checkout_cart', $cart);
        $this->dispatch('restoreCart', cart: $cart);
        $this->redirect('/checkout', navigate: true);
    }

    /**
     * Cancela um pedido pendente.
     */
    public function cancelOrder(int $orderId): void
    {
        $order = Auth::user()->orders()->findOrFail($orderId);

        if ($order->payment_status !== 'pendente') {
            $this->successMessage = 'Este pedido já foi pago e não pode ser cancelado.';
            return;
        }

        $order->update(['payment_status' => 'cancelado']);
        $this->successMessage = 'Pedido #' . $orderId . ' cancelado com sucesso.';
        $this->loadOrders();
    }

    /**
     * Abre o modal PIX com os dados do pedido.
     */
    public function payPixOrder(int $orderId): void
    {
        $order = Auth::user()->orders()->findOrFail($orderId);

        if ($order->payment_method !== 'pix' || $order->payment_status !== 'pendente') {
            return;
        }

        $this->pixOrderId = $order->id;
        $this->pixQrCodeBase64 = $order->pix_qr_code_base64 ?? '';
        $this->pixCopyPaste = $order->pix_copy_paste ?? $order->pix_qr_code ?? '';
        $this->pixConfirmed = false;
        $this->showPixModal = true;
    }

    /**
     * Polling: verifica se o pagamento foi confirmado.
     */
    public function checkPixPayment(): void
    {
        if (!$this->pixOrderId || !$this->showPixModal) {
            return;
        }

        $order = Order::find($this->pixOrderId);

        if ($order && ($order->payment_status === 'pago' || $order->payment_status === 'confirmado')) {
            $this->pixConfirmed = true;
            $this->showPixModal = false;
            $this->successMessage = 'Pagamento confirmado! Pedido #' . $this->pixOrderId . ' pago com sucesso! ✅';
            $this->loadOrders();
        }
    }

    /**
     * Fecha o modal PIX manualmente.
     */
    public function closePixModal(): void
    {
        $this->showPixModal = false;
        $this->successMessage = 'Pagamento pendente. Você pode pagar depois clicando em "Pagar com PIX".';
        $this->loadOrders();
    }

    public function render()
    {
        return view('livewire.customer-orders', [
            'isAuthenticated' => Auth::check(),
            'orderHistory' => $this->orderHistory,
        ]);
    }
}
