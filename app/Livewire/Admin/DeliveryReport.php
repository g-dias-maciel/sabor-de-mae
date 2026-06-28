<?php

namespace App\Livewire\Admin;

use App\Models\Menu;
use App\Models\Order;
use Livewire\Component;

class DeliveryReport extends Component
{
    public ?int $selectedMenuId = null;

    public function mount(): void
    {
        // Cardápio padrão: ativo, ou o último cadastrado
        $this->selectedMenuId = Menu::where('status', 'aberto')->value('id')
            ?? Menu::latest('start_date')->value('id');
    }

    public function updatedSelectedMenuId(): void
    {
        // Atualiza a view quando troca o cardápio
    }

    /**
     * Atualiza o status de entrega de um pedido.
     */
    public function updateStatus(int $orderId, string $status): void
    {
        $order = Order::findOrFail($orderId);
        $order->update(['delivery_status' => $status]);
    }

    /**
     * Lista de cardápios para o seletor (mais recentes primeiro).
     */
    public function getMenusProperty()
    {
        return Menu::orderByDesc('start_date')->get();
    }

    /**
     * Cardápio atualmente selecionado.
     */
    public function getMenuProperty(): ?Menu
    {
        return Menu::with('products')->find($this->selectedMenuId);
    }

    /**
     * Todos os pedidos do cardápio selecionado.
     */
    public function getPedidosProperty()
    {
        if (!$this->selectedMenuId) {
            return collect();
        }

        return Order::where('menu_id', $this->selectedMenuId)
            ->with(['user', 'items.product', 'deliveryZone'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Pedidos agrupados por dia da semana do item.
     */
    public function getPedidosPorDiaProperty(): array
    {
        // Mapa product_id => day_of_week
        $menu = $this->menu;
        $dayMap = $menu ? $menu->products->pluck('pivot.day_of_week', 'id')->toArray() : [];
        $pedidos = $this->pedidos;

        $dias = [
            1 => ['label' => '🥩 Segunda-feira',   'date' => null],
            2 => ['label' => '🥞 Terça-feira',     'date' => null],
            3 => ['label' => 'Quarta-feira',        'date' => null],
            4 => ['label' => '🥩 Quinta-feira',      'date' => null],
            5 => ['label' => '🍗 Sexta-feira',       'date' => null],
            6 => ['label' => '📦 Sábado',            'date' => null],
            7 => ['label' => '🍲 Domingo',           'date' => null],
        ];

        // Calcula as datas de cada dia
        if ($this->menu) {
            foreach ($dias as $day => &$info) {
                $info['date'] = $this->menu->start_date->copy()->addDays($day - 1);
            }
        }

        $grouped = [];
        foreach ($dias as $day => $info) {
            $grouped[$day] = [
                'label' => $info['label'],
                'date' => $info['date'],
                'orders' => [],
            ];
        }

        foreach ($pedidos as $pedido) {
            // Determina os dias deste pedido a partir dos itens
            $diasDoPedido = [];
            foreach ($pedido->items as $item) {
                $day = $dayMap[$item->product_id] ?? null;
                if ($day && !in_array($day, $diasDoPedido)) {
                    $diasDoPedido[] = $day;
                }
            }

            // Se tem itens sem dia definido (extras, pacotes), marca como dia 0
            $temSemDia = $pedido->items->contains(function ($item) use ($dayMap) {
                return !isset($dayMap[$item->product_id]) || $dayMap[$item->product_id] === null;
            });

            foreach ($diasDoPedido as $day) {
                if (isset($grouped[$day])) {
                    $grouped[$day]['orders'][] = $pedido;
                }
            }
        }

        // Remove dias sem pedidos
        return array_filter($grouped, fn($g) => count($g['orders']) > 0);
    }

    /**
     * Contagem por status de entrega.
     */
    public function getStatusCountsProperty(): array
    {
        $pedidos = $this->pedidos;

        return [
            'pendente' => $pedidos->where('delivery_status', 'pendente')->count(),
            'em_producao' => $pedidos->where('delivery_status', 'em_producao')->count(),
            'saiu_para_entrega' => $pedidos->where('delivery_status', 'saiu_para_entrega')->count(),
            'entregue' => $pedidos->where('delivery_status', 'entregue')->count(),
        ];
    }

    public function getTotalPedidosProperty(): int
    {
        return $this->pedidos->count();
    }

    public function render()
    {
        return view('livewire.admin.delivery-report')
            ->layout('layouts.admin');
    }
}
