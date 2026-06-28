<?php

namespace App\Livewire;

use App\Models\Menu;
use App\Models\Product;
use Livewire\Component;

class ProductList extends Component
{
    public ?Menu $menu = null;
    public array $cart = [];

    protected $listeners = [
        'cartUpdated' => '$refresh',
        'restoreCart' => 'onRestoreCart',
    ];

    public function mount(): void
    {
        $this->menu = Menu::ativo()->with('products')->first();

        // Restaura carrinho da sessão (persiste entre navegações)
        if (session()->has('checkout_cart')) {
            $this->cart = session()->get('checkout_cart');
            $this->dispatch('cartUpdated', cart: $this->cart);
        }
    }

    /**
     * Recebe um carrinho restaurado (ex: do "Repetir Pedido").
     */
    public function onRestoreCart(array $cart): void
    {
        $this->cart = $cart;
        $this->dispatch('cartUpdated', cart: $this->cart);
    }

    public function addToCart(int $productId, ?string $size = null, ?int $saladProductId = null): void
    {
        $product = Product::findOrFail($productId);

        // Para produtos com múltiplos tamanhos, o tamanho é obrigatório
        if ($product->hasSizes() && !$size) {
            return;
        }

        $size = $size ?: 'M';

        // Monta a chave: inclui salada se selecionada
        $saladSuffix = $saladProductId ? ":s{$saladProductId}" : '';
        $key = $productId . ':' . $size . $saladSuffix;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']++;
        } else {
            $productPrice = $product->prices()->where('size', $size)->first();
            $label = $product->name;
            if ($product->hasSizes()) {
                $label .= ' (' . $productPrice->shortLabel() . ')';
            }

            $item = [
                'product_id' => $productId,
                'name' => $label,
                'price' => $product->getPriceForSize($size),
                'quantity' => 1,
                'size' => $size,
                'salad_product_id' => null,
                'salad_name' => null,
            ];

            // Se selecionou salada, adiciona ao item
            if ($saladProductId) {
                $salad = Product::find($saladProductId);
                if ($salad) {
                    $item['salad_product_id'] = $saladProductId;
                    $item['salad_name'] = $salad->name;
                    $item['name'] .= ' + ' . $salad->name;
                    $item['price'] += $salad->getPriceForSize('M');
                }
            }

            $this->cart[$key] = $item;
        }

        $this->dispatch('cartUpdated', cart: $this->cart);
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
        $this->dispatch('cartUpdated', cart: $this->cart);
    }

    public function getCartTotalProperty(): float
    {
        return array_reduce($this->cart, function ($total, $item) {
            return $total + ($item['price'] * $item['quantity']);
        }, 0);
    }

    public function getCartCountProperty(): int
    {
        return array_reduce($this->cart, function ($count, $item) {
            return $count + $item['quantity'];
        }, 0);
    }

    /**
     * Retorna os produtos do menu agrupados por tipo e dia.
     */
    public function getGroupedProductsProperty(): array
    {
        if (!$this->menu) {
            return [];
        }

        $products = $this->menu->products()->withPivot('day_of_week')->get();

        $diasSemana = [
            1 => '🥩 Segunda-feira',
            2 => '🥞 Terça-feira',
            3 => 'Quarta-feira',
            4 => '🥩 Quinta-feira',
            5 => '🍗 Sexta-feira',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        // Refeições agrupadas por dia
        $refeicoesPorDia = [];
        foreach ($products as $product) {
            if ($product->isRefeicao()) {
                $dia = $product->pivot->day_of_week ?? 0;
                $refeicoesPorDia[$dia][] = $product;
            }
        }
        ksort($refeicoesPorDia);

        $groupedRefeicoes = [];
        foreach ($refeicoesPorDia as $dia => $prods) {
            $groupedRefeicoes[] = [
                'label' => $diasSemana[$dia] ?? 'Outro dia',
                'day' => $dia,
                'products' => $prods,
            ];
        }

        // Saladas agrupadas por dia
        $saladasPorDia = [];
        foreach ($products as $product) {
            if ($product->isSalada()) {
                $dia = $product->pivot->day_of_week ?? 0;
                $saladasPorDia[$dia][] = $product;
            }
        }

        // Pacote semanal
        $pacotes = $products->filter(fn($p) => $p->isPacoteSemanal());

        // Extras
        $extras = $products->filter(fn($p) => $p->isExtra());

        return [
            'refeicoes' => $groupedRefeicoes,
            'saladasPorDia' => $saladasPorDia,
            'pacotes' => $pacotes,
            'extras' => $extras,
        ];
    }

    /**
     * Retorna os tamanhos disponíveis para um produto (considera stock).
     */
    public function getAvailableSizesFor(Product $product): array
    {
        return $product->getAvailableSizes($this->menu);
    }

    public function render()
    {
        return view('livewire.product-list', [
            'grouped' => $this->groupedProducts,
        ]);
    }
}
