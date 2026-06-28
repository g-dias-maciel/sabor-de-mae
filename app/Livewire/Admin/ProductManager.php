<?php

namespace App\Livewire\Admin;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductPrice;
use Livewire\Component;

class ProductManager extends Component
{
    // ─── Produto ──────────────────────────────────────────
    public $editingProductId = null;
    public $productName;
    public $productDescription;
    public $productType = 'refeicao';
    public $productAvailable = true;

    // ─── Preços ───────────────────────────────────────────
    public $prices = []; // [ ['size' => 'P', 'price' => 19.00], ... ]

    // ─── UI ───────────────────────────────────────────────
    public $selectedProductId = null; // dropdown para selecionar produto a editar
    public $showForm = false;
    public $showDeleteConfirm = false;
    public $deleteProductId = null;
    public $message = null;
    public $messageType = 'success';

    protected $rules = [
        'productName'        => 'required|string|max:255',
        'productDescription' => 'nullable|string',
        'productType'        => 'required|in:refeicao,salada,pacote_semanal,extra',
        'productAvailable'   => 'boolean',
        'prices'             => 'required|array|min:1',
        'prices.*.size'      => 'required|string|in:P,M,G',
        'prices.*.price'     => 'required|numeric|min:0',
    ];

    public function render()
    {
        // Top 5 produtos mais pedidos (com contagem)
        // Usa leftJoin com COALESCE para não quebrar com zero pedidos
        $topProducts = Product::with('prices')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->select('products.*')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as order_items_sum_quantity')
            ->groupBy('products.id')
            ->orderByDesc('order_items_sum_quantity')
            ->limit(5)
            ->get();

        // Todos os produtos para o dropdown de seleção
        $allProducts = Product::with('prices')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.product-manager', compact('topProducts', 'allProducts'))
            ->layout('layouts.admin');
    }

    /**
     * Quando o dropdown de seleção muda, abre o formulário de edição.
     */
    public function updatedSelectedProductId($value): void
    {
        if ($value) {
            $this->openEditForm((int) $value);
            $this->selectedProductId = null; // reseta o dropdown
        }
    }

    // ─── Helpers ──────────────────────────────────────────

    private function flash(string $msg, string $type = 'success'): void
    {
        $this->message = $msg;
        $this->messageType = $type;
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingProductId = null;
        $this->reset(['productName', 'productDescription', 'productType', 'productAvailable', 'prices']);
        $this->resetErrorBag();
    }

    // ─── CRUD ─────────────────────────────────────────────

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->productType = 'refeicao';
        $this->productAvailable = true;
        $this->prices = [['size' => 'M', 'price' => 0]];
        $this->showForm = true;
    }

    public function openEditForm(int $productId): void
    {
        $this->resetForm();
        $product = Product::with('prices')->findOrFail($productId);

        $this->editingProductId = $product->id;
        $this->productName = $product->name;
        $this->productDescription = $product->description;
        $this->productType = $product->type;
        $this->productAvailable = $product->is_available;

        $this->prices = $product->prices->map(fn($p) => [
            'id'    => $p->id,
            'size'  => $p->size,
            'price' => (float) $p->price,
        ])->toArray();

        if (empty($this->prices)) {
            $this->prices = [['size' => 'M', 'price' => 0]];
        }

        $this->showForm = true;
    }

    public function saveProduct(): void
    {
        $this->validate();

        if ($this->editingProductId) {
            $product = Product::findOrFail($this->editingProductId);
            $product->update([
                'name'         => $this->productName,
                'description'  => $this->productDescription,
                'type'         => $this->productType,
                'is_available' => $this->productAvailable,
            ]);

            // Sincroniza preços: remove os que não estão mais na lista e atualiza/cria
            $keptIds = collect($this->prices)->pluck('id')->filter()->toArray();
            $product->prices()->whereNotIn('id', $keptIds)->delete();

            foreach ($this->prices as $p) {
                if (!empty($p['id'])) {
                    ProductPrice::where('id', $p['id'])->update([
                        'size'  => $p['size'],
                        'price' => $p['price'],
                    ]);
                } else {
                    $product->prices()->create([
                        'size'  => $p['size'],
                        'price' => $p['price'],
                    ]);
                }
            }
        } else {
            $product = Product::create([
                'name'         => $this->productName,
                'description'  => $this->productDescription,
                'type'         => $this->productType,
                'is_available' => $this->productAvailable,
            ]);

            foreach ($this->prices as $p) {
                $product->prices()->create([
                    'size'  => $p['size'],
                    'price' => $p['price'],
                ]);
            }
        }

        $this->flash($this->editingProductId ? 'Produto atualizado! 🍽️' : 'Produto criado! 🍽️');
        $this->resetForm();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    // ─── Preços dinâmicos ─────────────────────────────────

    public function addPrice(): void
    {
        $this->prices[] = ['size' => 'M', 'price' => 0];
    }

    public function removePrice(int $index): void
    {
        if (count($this->prices) > 1) {
            unset($this->prices[$index]);
            $this->prices = array_values($this->prices);
        }
    }

    // ─── Excluir ──────────────────────────────────────────

    public function confirmDelete(int $productId): void
    {
        $this->showDeleteConfirm = true;
        $this->deleteProductId = $productId;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteProductId = null;
    }

    public function deleteProduct(): void
    {
        $product = Product::findOrFail($this->deleteProductId);

        if ($product->orderItems()->count() > 0) {
            $this->flash('Não é possível excluir um produto que já possui pedidos.', 'error');
        } else {
            $product->prices()->delete();
            $product->delete();
            $this->flash('Produto excluído.');
        }

        $this->showDeleteConfirm = false;
        $this->deleteProductId = null;
    }

    // ─── Toggle disponibilidade ───────────────────────────

    public function toggleAvailable(int $productId): void
    {
        $product = Product::findOrFail($productId);
        $product->update(['is_available' => !$product->is_available]);
        $this->flash($product->is_available ? 'Produto disponível ✅' : 'Produto indisponível 🚫');
    }
}
