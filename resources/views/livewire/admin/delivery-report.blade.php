<div class="fade-in">
    {{-- Cabeçalho com Seletor de Cardápio --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <h1 class="title-hand text-4xl md:text-5xl text-brown-dark mb-1">Entregas da Semana</h1>
            <p class="text-brown-light">Pedidos organizados por dia de entrega</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Seletor de cardápio/semana --}}
            <select wire:model.live="selectedMenuId" class="input-warm text-sm">
                @foreach($this->menus as $m)
                    <option value="{{ $m->id }}">
                        {{ $m->start_date->format('d/m') }} a {{ $m->end_date->format('d/m') }}
                        @if($m->status === 'aberto')
                            (atual)
                        @elseif($m->status === 'encerrado')
                            (encerrado)
                        @endif
                    </option>
                @endforeach
            </select>
            <button
                onclick="window.print()"
                class="btn-olive print:hidden text-sm"
            >
                🖨️ Imprimir
            </button>
        </div>
    </div>

    {{-- Info do Cardápio --}}
    @if($this->menu)
        <div class="mb-6 flex items-center gap-3">
            <span class="inline-flex items-center gap-1 px-4 py-1.5 rounded-full text-sm font-bold
                {{ $this->menu->status === 'aberto' ? 'bg-olive-lighter text-olive-darker' : 'bg-cream-dark text-brown-light' }}">
                @if($this->menu->status === 'aberto')
                    ✅ Cardápio atual
                @else
                    📋 Cardápio encerrado
                @endif
            </span>
            <span class="text-brown-light text-sm">
                {{ $this->menu->start_date->format('d/m/Y') }} — {{ $this->menu->end_date->format('d/m/Y') }}
            </span>
        </div>
    @endif

    {{-- Cards de Status --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="card-artisan text-center">
            <div class="p-4">
                <p class="text-3xl font-extrabold text-brown-dark">{{ $this->statusCounts['pendente'] }}</p>
                <p class="text-sm font-bold text-brown-light mt-1">⏳ Pendentes</p>
            </div>
        </div>
        <div class="card-artisan text-center">
            <div class="p-4">
                <p class="text-3xl font-extrabold text-olive-dark">{{ $this->statusCounts['em_producao'] }}</p>
                <p class="text-sm font-bold text-brown-light mt-1">👩‍🍳 Em Produção</p>
            </div>
        </div>
        <div class="card-artisan text-center">
            <div class="p-4">
                <p class="text-3xl font-extrabold text-terracotta">{{ $this->statusCounts['saiu_para_entrega'] }}</p>
                <p class="text-sm font-bold text-brown-light mt-1">🛵 Saiu para Entrega</p>
            </div>
        </div>
        <div class="card-artisan text-center">
            <div class="p-4">
                <p class="text-3xl font-extrabold text-olive-darker">{{ $this->statusCounts['entregue'] }}</p>
                <p class="text-sm font-bold text-brown-light mt-1">✅ Entregues</p>
            </div>
        </div>
    </div>

    @if($this->totalPedidos === 0)
        <div class="text-center py-16">
            <p class="text-6xl mb-4">📭</p>
            <p class="text-2xl text-brown-light">Nenhum pedido para esta semana.</p>
        </div>
    @else
        {{-- ===== Pedidos por Dia ===== --}}
        <div class="space-y-6">
            @foreach($this->pedidosPorDia as $day => $grupo)
                @php
                    $count = count($grupo['orders']);
                    $dateStr = $grupo['date'] ? $grupo['date']->format('d/m') : '';
                @endphp
                <div class="card-artisan overflow-hidden">
                    {{-- Cabeçalho do Dia --}}
                    <div class="bg-gradient-to-r from-terracotta-lighter to-olive-lighter px-6 py-4 border-b-2 border-cream-darker flex justify-between items-center">
                        <h2 class="text-xl font-extrabold text-brown-dark">
                            {{ $grupo['label'] }}
                            @if($dateStr)
                                <span class="text-brown-light text-base ml-2">— {{ $dateStr }}</span>
                            @endif
                        </h2>
                        <span class="badge-terracotta text-sm">
                            {{ $count }} {{ $count === 1 ? 'pedido' : 'pedidos' }}
                        </span>
                    </div>

                    {{-- Tabela de Pedidos do Dia --}}
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-cream-darker bg-cream">
                                    <th class="p-3 text-left text-xs font-extrabold text-brown-dark uppercase tracking-wide">Pedido</th>
                                    <th class="p-3 text-left text-xs font-extrabold text-brown-dark uppercase tracking-wide">Cliente</th>
                                    <th class="p-3 text-left text-xs font-extrabold text-brown-dark uppercase tracking-wide hidden md:table-cell">Itens</th>
                                    <th class="p-3 text-left text-xs font-extrabold text-brown-dark uppercase tracking-wide hidden lg:table-cell">Endereço</th>
                                    <th class="p-3 text-left text-xs font-extrabold text-brown-dark uppercase tracking-wide">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grupo['orders'] as $pedido)
                                    <tr class="border-t border-cream-darker hover:bg-cream transition-colors">
                                        <td class="p-3">
                                            <span class="font-bold text-brown-dark">#{{ $pedido->id }}</span>
                                        </td>
                                        <td class="p-3">
                                            <p class="font-bold text-brown-dark text-sm">{{ $pedido->user->name }}</p>
                                            @if($pedido->user->phone)
                                                <p class="text-xs text-brown-light">{{ $pedido->user->phone }}</p>
                                            @endif
                                        </td>
                                        <td class="p-3 hidden md:table-cell">
                                            @foreach($pedido->items as $item)
                                                <div class="text-sm">
                                                    <span class="font-bold text-brown-dark">{{ $item->quantity }}x</span>
                                                    <span class="text-brown-light">{{ $item->product->name }}</span>
                                                    @if($item->size && $item->size !== 'M')
                                                        <span class="text-xs text-terracotta font-bold">({{ $item->size }})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </td>
                                        <td class="p-3 hidden lg:table-cell">
                                            <p class="text-sm text-brown-light max-w-48 truncate">{{ $pedido->delivery_address }}</p>
                                            @if($pedido->deliveryZone)
                                                <p class="text-xs text-brown-lighter">{{ $pedido->deliveryZone->neighborhood }}</p>
                                            @endif
                                        </td>
                                        <td class="p-3 print:hidden">
                                            <select
                                                wire:change="updateStatus({{ $pedido->id }}, $event.target.value)"
                                                class="input-warm text-sm py-1 px-2"
                                            >
                                                @foreach(['pendente', 'em_producao', 'saiu_para_entrega', 'entregue'] as $status)
                                                    <option value="{{ $status }}" @selected($pedido->delivery_status === $status)>
                                                        {{ match($status) {
                                                            'pendente' => '⏳ Pendente',
                                                            'em_producao' => '👩‍🍳 Em Produção',
                                                            'saiu_para_entrega' => '🛵 Saiu para Entrega',
                                                            'entregue' => '✅ Entregue',
                                                            default => $status,
                                                        } }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        <style>
            @media print {
                body { font-size: 12pt; }
                nav, .print\:hidden { display: none !important; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #000; padding: 6px; }
                th { background: #eee !important; }
                .card-artisan { box-shadow: none; border: 1px solid #ccc; break-inside: avoid; margin-bottom: 16px; }
                .card-artisan::before { display: none; }
            }
        </style>
    @endif
</div>
