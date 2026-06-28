<?php

namespace App\Livewire\Admin;

use App\Models\Menu;
use App\Models\Order;
use Livewire\Component;

class Dashboard extends Component
{
    public function getFaturamentoProperty(): float
    {
        $menu = Menu::ativo()->first();

        if (!$menu) {
            return 0;
        }

        return (float) Order::where('menu_id', $menu->id)
            ->where('payment_status', 'confirmado')
            ->sum('total');
    }

    public function getTotalMarmitasProperty(): int
    {
        $menu = Menu::ativo()->first();

        if (!$menu) {
            return 0;
        }

        return (int) \App\Models\OrderItem::whereHas('order', function ($query) use ($menu) {
            $query->where('menu_id', $menu->id)
                ->where('payment_status', 'confirmado');
        })->sum('quantity');
    }

    public function getStatusMenuProperty(): array
    {
        $menu = Menu::ativo()->first();

        if (!$menu) {
            return [
                'status' => 'Nenhum',
                'label' => 'Não há menu ativo',
                'color' => 'gray',
            ];
        }

        $statusLabels = [
            'planejamento' => ['label' => 'Em Planejamento', 'color' => 'yellow'],
            'aberto' => ['label' => 'Aberto para Pedidos', 'color' => 'green'],
            'encerrado' => ['label' => 'Encerrado', 'color' => 'red'],
        ];

        return [
            'status' => $menu->status,
            'label' => $statusLabels[$menu->status]['label'],
            'color' => $statusLabels[$menu->status]['color'],
            'inicio' => $menu->start_date->format('d/m'),
            'fim' => $menu->end_date->format('d/m'),
        ];
    }

    public function render()
    {
        return view('livewire.admin.dashboard')
            ->layout('layouts.admin');
    }
}
