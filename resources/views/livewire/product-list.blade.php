<div class="container mx-auto px-4 py-8 md:py-12">
    @if(!$menu)
        {{-- Estado Vazio --}}
        <div class="text-center py-16 fade-in">
            <div class="text-6xl mb-6">🍲</div>
            <h2 class="title-hand text-4xl md:text-5xl text-brown-dark mb-4">Em breve no fogão!</h2>
            <p class="text-brown-light text-lg max-w-md mx-auto leading-relaxed">
                Nenhum cardápio disponível no momento. Volte em breve para saborear nossas delícias caseiras!
            </p>
            <div class="leaf-divider mt-8">
                <span>🌿</span>
                <span>🌿</span>
            </div>
        </div>
    @else
        {{-- Cabecalho --}}
        <div class="text-center mb-10 fade-in">
            <h1 class="title-hand text-4xl md:text-6xl text-brown-dark mb-3">
                Cardápio da Semana
            </h1>
            <p class="text-brown-light text-lg mb-4">
                <span class="inline-flex items-center gap-2">
                    <span class="leaf-accent">🌿</span>
                    {{ $menu->start_date->format('d/m') }} a {{ $menu->end_date->format('d/m') }}
                    <span class="leaf-accent">🌿</span>
                </span>
            </p>

            @if($menu->aceitaPedidos())
                <div class="inline-flex items-center gap-2 bg-olive-lighter text-olive-darker font-bold px-5 py-2 rounded-full border-2 border-olive-light">
                    <span>✅</span>
                    @if(now()->isSunday())
                        Pedidos abertos — pré-venda da próxima semana
                    @else
                        Pedidos abertos até sábado 23:59
                    @endif
                </div>
            @else
                <div class="inline-flex items-center gap-2 bg-terracotta-lighter text-terracotta-darker font-bold px-5 py-2 rounded-full border-2 border-terracotta-light">
                    <span>🔒</span> Pedidos encerrados esta semana
                </div>
            @endif
        </div>

        {{-- ===== REFEIÇÕES POR DIA ===== --}}
        @foreach($grouped['refeicoes'] as $grupo)
            @php
                $dia = $grupo['day'];
                $saladasDoDia = $grouped['saladasPorDia'][$dia] ?? [];
            @endphp
            <div class="mb-10 fade-in max-w-6xl mx-auto">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-terracotta-lighter flex items-center justify-center text-xl">
                        {{ match($dia) { 1 => '🥩', 2 => '🥞', 4 => '🧆', 5 => '🍗', default => '🍲' } }}
                    </div>
                    <h2 class="title-hand text-3xl md:text-4xl text-brown-dark">{{ $grupo['label'] }}</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-1 xl:grid-cols-1 gap-5">
                    @foreach($grupo['products'] as $product)
                        @php
                            $availableSizes = $this->getAvailableSizesFor($product);
                            $hasSizes = $product->hasSizes();
                        @endphp
                        <div class="card-artisan flex flex-col p-0">
                            <div class="p-5 flex flex-col flex-1">
                                {{-- Nome do prato --}}
                                <h3 class="text-lg font-extrabold text-brown-dark leading-snug mb-2">
                                    {{ $product->name }}
                                </h3>

                                {{-- Descricao --}}
                                <p class="text-brown-light text-sm leading-relaxed mb-4 flex-1 whitespace-pre-line">
                                    {{ $product->description }}
                                </p>

                                {{-- Seletor de Salada (se houver saladas neste dia) --}}
                                @if(count($saladasDoDia) > 0 && $menu->aceitaPedidos())
                                    <div class="mb-3">
                                        <p class="text-xs font-bold text-brown-lighter uppercase tracking-wide mb-2">
                                            🥗 Adicionar salada
                                        </p>
                                        <select
                                            x-ref="salad_{{ $product->id }}"
                                            class="input-warm w-full text-sm"
                                        >
                                            <option value="">Sem salada</option>
                                            @foreach($saladasDoDia as $salada)
                                                @php $saladPrice = $salada->getPriceForSize('M'); @endphp
                                                <option value="{{ $salada->id }}">
                                                    {{ $salada->name }} + R$ {{ number_format($saladPrice, 2, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                {{-- Seletor de Tamanho + Preços --}}
                                @if($hasSizes)
                                    <div class="mb-4">
                                        <p class="text-xs font-bold text-brown-lighter uppercase tracking-wide mb-2">
                                            🥡 Tamanho da porção
                                        </p>
                                        <div class="grid grid-cols-2 gap-2" x-data="{ size: null }">
                                            @foreach($product->prices as $productPrice)
                                                @php
                                                    $isAvailable = collect($availableSizes)->contains(fn($p) => $p->size === $productPrice->size);
                                                    $isP = $productPrice->size === 'P';
                                                    $isG = $productPrice->size === 'G';
                                                @endphp
                                                @if($isP || $isG)
                                                    <button
                                                        type="button"
                                                        @click="size = '{{ $productPrice->size }}'"
                                                        :class="size === '{{ $productPrice->size }}'
                                                            ? 'border-terracotta bg-terracotta-lighter ring-2 ring-terracotta-light'
                                                            : 'border-cream-darker hover:border-terracotta-light'"
                                                        class="border-2 rounded-xl p-3 text-left transition-all cursor-pointer bg-white
                                                            {{ !$isAvailable ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                        @if(!$isAvailable) disabled @endif
                                                    >
                                                        <div class="flex justify-between items-baseline">
                                                            <span class="font-bold text-brown-dark text-sm">
                                                                {{ $productPrice->shortLabel() }}
                                                            </span>
                                                            <span class="text-xs text-brown-light">
                                                                ~{{ $isP ? '500g' : '750g' }}
                                                            </span>
                                                        </div>
                                                        <p class="text-xl font-extrabold text-terracotta mt-1">
                                                            R$ {{ number_format($productPrice->price, 2, ',', '.') }}
                                                        </p>
                                                        @if(!$isAvailable)
                                                            <p class="text-xs text-red-500 font-bold mt-1">Esgotado</p>
                                                        @endif
                                                    </button>
                                                @endif
                                            @endforeach

                                            {{-- Botão Adicionar (só aparece depois de selecionar) --}}
                                            @if($menu->aceitaPedidos())
                                                <div class="col-span-2 mt-1" x-show="size" x-transition>
                                                    <button
                                                        wire:click="addToCart({{ $product->id }}, size, $refs.salad_{{ $product->id }}?.value || null)"
                                                        @click="size = null"
                                                        class="btn-terracotta w-full text-sm py-3"
                                                    >
                                                        + Adicionar ao Carrinho
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    {{-- Produto sem variação de tamanho (ex: salada, extra) --}}
                                    @php
                                        $singlePrice = $product->prices->first();
                                    @endphp
                                    <div class="mb-4">
                                        <p class="text-xl font-extrabold text-terracotta">
                                            R$ {{ number_format($singlePrice?->price ?? 0, 2, ',', '.') }}
                                        </p>
                                    </div>
                                    @if($menu->aceitaPedidos())
                                        <button
                                            wire:click="addToCart({{ $product->id }})"
                                            class="btn-terracotta w-full text-sm py-3 mt-auto"
                                        >
                                            + Adicionar ao Carrinho
                                        </button>
                                    @endif
                                @endif

                                @if(!$menu->aceitaPedidos())
                                    <p class="text-center text-brown-lighter text-sm italic mt-auto pt-2 border-t border-cream-darker">
                                        Pedidos encerrados
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Divisor entre dias (exceto último) --}}
            @if(!$loop->last || count($grouped['pacotes']) > 0)
                <div class="leaf-divider text-lg mb-6">
                    <span>🌿</span>
                </div>
            @endif
        @endforeach

        {{-- ===== PACOTE SEMANAL ===== --}}
        @if(count($grouped['pacotes']) > 0)
            <div class="mb-10 fade-in max-w-6xl mx-auto">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-olive-lighter flex items-center justify-center text-xl">
                        ⭐
                    </div>
                    <h2 class="title-hand text-3xl md:text-4xl text-brown-dark">Pacote Semanal</h2>
                    <span class="badge-terracotta text-xs">Mais econômico!</span>
                </div>

                @foreach($grouped['pacotes'] as $pacote)
                    @php
                        $availableSizes = $this->getAvailableSizesFor($pacote);
                    @endphp
                    <div class="card-artisan max-w-2xl mx-auto">
                        <div class="p-6">
                            <h3 class="text-xl font-extrabold text-brown-dark mb-3">{{ $pacote->name }}</h3>
                            <p class="text-brown-light text-sm leading-relaxed mb-4 whitespace-pre-line">{{ $pacote->description }}</p>

                            @if($pacote->hasSizes() && $menu->aceitaPedidos())
                                <div x-data="{ size: null }">
                                    <p class="text-xs font-bold text-brown-lighter uppercase tracking-wide mb-3">
                                        🥡 Escolha o tamanho
                                    </p>
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        @foreach($pacote->prices as $productPrice)
                                            @php
                                                $isAvailable = collect($availableSizes)->contains(fn($p) => $p->size === $productPrice->size);
                                                $isP = $productPrice->size === 'P';
                                                $isG = $productPrice->size === 'G';
                                            @endphp
                                            @if($isP || $isG)
                                                <button
                                                    type="button"
                                                    @click="size = '{{ $productPrice->size }}'"
                                                    :class="size === '{{ $productPrice->size }}'
                                                        ? 'border-terracotta bg-terracotta-lighter ring-2 ring-terracotta-light'
                                                        : 'border-cream-darker hover:border-terracotta-light'"
                                                    class="border-2 rounded-xl p-4 text-left transition-all cursor-pointer bg-white
                                                        {{ !$isAvailable ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                    @if(!$isAvailable) disabled @endif
                                                >
                                                    <div class="flex justify-between items-baseline mb-1">
                                                        <span class="font-bold text-brown-dark">
                                                            {{ $productPrice->shortLabel() }}
                                                        </span>
                                                        <span class="text-xs text-brown-light">
                                                            ~{{ $isP ? '500g' : '750g' }}
                                                        </span>
                                                    </div>
                                                    <p class="text-2xl font-extrabold text-terracotta">
                                                        R$ {{ number_format($productPrice->price, 2, ',', '.') }}
                                                    </p>
                                                    <p class="text-xs text-brown-lighter mt-1">4 almoços</p>
                                                    @if(!$isAvailable)
                                                        <p class="text-xs text-red-500 font-bold mt-1">Esgotado</p>
                                                    @endif
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>

                                    <button
                                        x-show="size"
                                        x-transition
                                        wire:click="addToCart({{ $pacote->id }}, size)"
                                        @click="size = null"
                                        class="btn-terracotta w-full"
                                    >
                                        ⭐ Adicionar Pacote Semanal
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="leaf-divider text-lg mb-6">
                <span>🌿</span>
            </div>
        @endif

        {{-- ===== EXTRAS ===== --}}
        @if(count($grouped['extras']) > 0)
            <div class="mb-10 fade-in max-w-6xl mx-auto">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-olive-lighter flex items-center justify-center text-xl">
                        🥗
                    </div>
                    <h2 class="title-hand text-3xl md:text-4xl text-brown-dark">Extras</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($grouped['extras'] as $extra)
                        <div class="card-artisan">
                            <div class="p-5">
                                <h3 class="text-lg font-extrabold text-brown-dark mb-2">{{ $extra->name }}</h3>
                                <p class="text-brown-light text-sm mb-3">{{ $extra->description }}</p>
                                <div class="flex items-center justify-between">
                                    <p class="text-2xl font-extrabold text-terracotta">
                                        R$ {{ number_format($extra->getPriceForSize('M'), 2, ',', '.') }}
                                    </p>
                                    @if($menu->aceitaPedidos())
                                        <button
                                            wire:click="addToCart({{ $extra->id }})"
                                            class="btn-olive text-sm"
                                        >
                                            + Adicionar
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Itens no carrinho --}}
        @if(count($cart) > 0)
            <div class="leaf-divider text-lg mt-8 mb-4">
                <span>🛒</span>
                <span class="text-xs text-brown-lighter tracking-widest uppercase font-body">Seu carrinho</span>
                <span>🛒</span>
            </div>
            <div class="max-w-xl mx-auto space-y-2">
                @foreach($cart as $key => $item)
                    <div class="flex items-center justify-between bg-white rounded-xl p-3 border border-cream-darker shadow-sm">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-brown-dark text-sm truncate">{{ $item['name'] }}</p>
                            <p class="text-xs text-brown-light">
                                {{ $item['quantity'] }}x R$ {{ number_format($item['price'], 2, ',', '.') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3 ml-3">
                            <span class="font-extrabold text-terracotta text-sm">
                                R$ {{ number_format($item['price'] * $item['quantity'], 2, ',', '.') }}
                            </span>
                            <button
                                wire:click="removeFromCart('{{ $key }}')"
                                class="text-brown-lighter hover:text-red-500 transition-colors font-bold"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
