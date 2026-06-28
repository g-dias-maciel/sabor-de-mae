<div class="fade-in">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <h1 class="title-hand text-4xl md:text-5xl text-brown-dark mb-1">Dashboard</h1>
            <p class="text-brown-light">Visão geral da semana</p>
        </div>
        <div class="text-sm text-brown-lighter bg-cream-dark rounded-full px-4 py-2">
            👩‍🍳 Painel da Mãe
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Card Faturamento --}}
        <div class="card-artisan">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-olive-lighter flex items-center justify-center text-2xl">
                        💰
                    </div>
                    <h2 class="text-lg font-extrabold text-brown-dark">Total Faturado</h2>
                </div>
                <p class="text-4xl font-extrabold text-olive-dark">
                    R$ {{ number_format($this->faturamento, 2, ',', '.') }}
                </p>
                <p class="text-brown-light text-sm mt-1">na semana atual</p>
            </div>
        </div>

        {{-- Card Marmitas Vendidas --}}
        <div class="card-artisan">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-terracotta-lighter flex items-center justify-center text-2xl">
                        🍱
                    </div>
                    <h2 class="text-lg font-extrabold text-brown-dark">Marmitas Vendidas</h2>
                </div>
                <p class="text-4xl font-extrabold text-terracotta">
                    {{ $this->totalMarmitas }}
                </p>
                <p class="text-brown-light text-sm mt-1">unidades na semana</p>
            </div>
        </div>

        {{-- Card Status do Menu --}}
        @php
            $statusColor = $this->statusMenu['color'] ?? 'gray';
            $colorMap = [
                'green' => ['bg' => 'bg-olive-lighter', 'text' => 'text-olive-dark', 'dot' => 'bg-olive'],
                'yellow' => ['bg' => 'bg-terracotta-lighter', 'text' => 'text-terracotta-darker', 'dot' => 'bg-terracotta'],
                'red' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'dot' => 'bg-red-500'],
                'gray' => ['bg' => 'bg-cream-dark', 'text' => 'text-brown-light', 'dot' => 'bg-brown-lighter'],
            ][$statusColor] ?? ['bg' => 'bg-cream-dark', 'text' => 'text-brown-light', 'dot' => 'bg-brown-lighter'];
        @endphp

        <div class="card-artisan">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full {{ $colorMap['bg'] }} flex items-center justify-center text-2xl">
                        📋
                    </div>
                    <h2 class="text-lg font-extrabold text-brown-dark">Status do Menu</h2>
                </div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-3 h-3 rounded-full {{ $colorMap['dot'] }} inline-block"></span>
                    <p class="text-2xl font-extrabold {{ $colorMap['text'] }}">
                        {{ $this->statusMenu['label'] }}
                    </p>
                </div>
                @if(isset($this->statusMenu['inicio']))
                    <p class="text-brown-light text-sm mt-1">
                        {{ $this->statusMenu['inicio'] }} — {{ $this->statusMenu['fim'] }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
