<?php

namespace App\Livewire\Admin;

use App\Models\Menu;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Livewire\Component;

class ShoppingList extends Component
{
    public function getProdutosAgregadosProperty(): Collection
    {
        $menu = Menu::ativo()->first();

        if (!$menu) {
            return collect();
        }

        return OrderItem::selectRaw(
                'product_id, size, SUM(quantity) as total_quantidade'
            )
            ->whereHas('order', function ($query) use ($menu) {
                $query->where('menu_id', $menu->id)
                    ->where('payment_status', 'confirmado');
            })
            ->with('product')
            ->groupBy('product_id', 'size')
            ->get()
            ->sortByDesc('total_quantidade');
    }

    public function getTotalGeralProperty(): int
    {
        return $this->produtosAgregados->sum('total_quantidade');
    }

    public function render()
    {
        return view('livewire.admin.shopping-list')
            ->layout('layouts.admin');
    }
}
