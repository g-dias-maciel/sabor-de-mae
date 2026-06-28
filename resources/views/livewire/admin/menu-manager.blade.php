<div class="fade-in" x-data="{ showMsg: @json($message ? true : false) }">
    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="title-hand text-3xl md:text-4xl text-brown-dark">📅 Cardápios Semanais</h1>
            <p class="text-brown-light text-sm mt-1">Gerencie os cardápios e os produtos de cada semana</p>
        </div>
        @if(!$showForm)
            <button wire:click="openCreateForm" class="btn-terracotta text-sm">
                + Novo Cardápio
            </button>
        @endif
    </div>

    {{-- Mensagem Flash --}}
    @if($message)
        <div
            x-show="showMsg"
            x-transition
            x-init="setTimeout(() => showMsg = false, 4000)"
            class="mb-6 p-4 rounded-xl border-l-4 text-sm font-bold fade-in {{ $messageType === 'error' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-olive-lighter border-olive text-olive-darker' }}"
        >
            {{ $message }}
        </div>
    @endif

    {{-- ===== FORMULÁRIO DE CRIAÇÃO/EDIÇÃO ===== --}}
    @if($showForm)
        <div class="card-artisan p-6 mb-8">
            <h2 class="title-hand text-2xl text-brown-dark mb-6">
                {{ $editingMenu ? '✏️ Editar Cardápio' : '🆕 Novo Cardápio' }}
            </h2>

            {{-- Datas e Status --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <div>
                    <label class="block text-sm font-extrabold text-brown-dark mb-2">Segunda-feira da Semana</label>
                    <input type="date" wire:model="menuStartDate" class="input-warm w-full">
                    @error('menuStartDate') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    @if($menuStartDate)
                        <p class="text-xs text-brown-lighter mt-1">
                            Período: {{ \Carbon\Carbon::parse($menuStartDate)->format('d/m') }}
                            a {{ \Carbon\Carbon::parse($menuStartDate)->addDays(6)->format('d/m/Y') }}
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-extrabold text-brown-dark mb-2">Status</label>
                    <select wire:model="menuStatus" class="input-warm w-full">
                        <option value="planejamento">📋 Planejamento</option>
                        <option value="aberto">✅ Aberto (aceita pedidos)</option>
                        <option value="encerrado">🔒 Encerrado</option>
                    </select>
                </div>
            </div>

            {{-- Seleção de Produtos por Dia --}}
            <div class="border-t border-cream-darker pt-6">
                <h3 class="font-extrabold text-brown-dark mb-4 flex items-center gap-2">
                    <span>🍽️</span> Produtos do Cardápio
                </h3>
                <p class="text-xs text-brown-lighter mb-6">
                    Selecione a refeição e a salada (opcional) para cada dia.
                    Pacote semanal e extras ficam disponíveis todos os dias.
                </p>

                {{-- Cards por dia da semana --}}
                <div class="space-y-4 mb-8">
                    @foreach(range(1, 7) as $day)
                        @php
                            $selectedMealId = $dayMeals[$day] ?? null;
                            $selectedSaladId = $daySalads[$day] ?? null;
                            $selectedMeal = $selectedMealId ? ($productsById[$selectedMealId] ?? null) : null;
                            $selectedSalad = $selectedSaladId ? ($productsById[$selectedSaladId] ?? null) : null;
                            $dayEmoji = match($day) {
                                1 => '🥩', 2 => '🥞', 3 => '🍲', 4 => '🧆',
                                5 => '🍗', 6 => '🍽️', 7 => '🍲',
                                default => '🍽️',
                            };
                        @endphp
                        <div class="card-artisan p-5 {{ $selectedMeal ? 'border-l-4 border-l-terracotta' : '' }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Coluna 1: Refeição --}}
                                <div>
                                    <label class="block text-sm font-extrabold text-brown-dark mb-2 flex items-center gap-2">
                                        <span>{{ $dayEmoji }}</span> {{ $dayLabels[$day] }}
                                    </label>
                                    <select wire:model.live="dayMeals.{{ $day }}" class="input-warm w-full text-sm">
                                        <option value="">— Nenhuma refeição —</option>
                                        @foreach($refeicoes as $refeicao)
                                            <option value="{{ $refeicao->id }}">
                                                {{ $refeicao->name }}
                                    @if($refeicao->prices->isNotEmpty())
                                                        ({{ $refeicao->prices->pluck('size')->implode('/') }})
                                                    @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- Detalhes da refeição selecionada --}}
                                    @if($selectedMeal)
                                        <div class="mt-3 bg-cream rounded-xl p-3 text-sm">
                                            @if($selectedMeal->description)
                                                <p class="text-brown-light whitespace-pre-line mb-2">{{ $selectedMeal->description }}</p>
                                            @endif
                                            <p class="text-terracotta font-bold">
                                                @foreach($selectedMeal->prices as $price)
                                                    {{ $price->shortLabel() }}: R$ {{ number_format($price->price, 2, ',', '.') }}
                                                    @if(!$loop->last) · @endif
                                                @endforeach
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Coluna 2: Salada (opcional) --}}
                                <div>
                                    <label class="block text-sm font-extrabold text-brown-dark mb-2 flex items-center gap-2">
                                        <span>🥗</span> Salada (opcional)
                                    </label>
                                    <select wire:model.live="daySalads.{{ $day }}" class="input-warm w-full text-sm">
                                        <option value="">— Sem salada —</option>
                                        @foreach($saladas as $salada)
                                            <option value="{{ $salada->id }}">
                                                {{ $salada->name }}
                                                @php $saladPrice = $salada->getPriceForSize('M'); @endphp
                                                + R$ {{ number_format($saladPrice, 2, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- Detalhes da salada selecionada --}}
                                    @if($selectedSalad)
                                        <div class="mt-3 bg-green-50 rounded-xl p-3 text-sm">
                                            @if($selectedSalad->description)
                                                <p class="text-brown-light mb-1">{{ $selectedSalad->description }}</p>
                                            @endif
                                            <p class="text-green-700 font-bold">
                                                M: R$ {{ number_format($selectedSalad->getPriceForSize('M'), 2, ',', '.') }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pacote Semanal --}}
                <div class="card-artisan p-5 mb-4 border-l-4 border-l-olive">
                    <h4 class="font-extrabold text-brown-dark flex items-center gap-2 mb-3">
                        <span>⭐</span> Pacote Semanal
                        <span class="badge-terracotta text-xs">4 almoços com desconto</span>
                    </h4>
                    <select wire:model.live="weeklyPackageId" class="input-warm w-full text-sm">
                        <option value="">— Nenhum pacote —</option>
                        @foreach($pacotes as $pacote)
                            <option value="{{ $pacote->id }}">
                                {{ $pacote->name }}
                                @if($pacote->prices->isNotEmpty())
                                    @php
                                        $pricesStr = $pacote->prices->map(function($p) {
                                            return $p->shortLabel().': R$ '.number_format($p->price, 2, ',', '.');
                                        })->implode(' | ');
                                    @endphp
                                    ({{ $pricesStr }})
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @if($weeklyPackageId && isset($productsById[$weeklyPackageId]))
                        @php $selectedPacote = $productsById[$weeklyPackageId]; @endphp
                        <div class="mt-3 bg-olive-lighter rounded-xl p-3 text-sm">
                            @if($selectedPacote->description)
                                <p class="text-olive-darker whitespace-pre-line mb-2">{{ $selectedPacote->description }}</p>
                            @endif
                            <p class="text-olive-darker font-bold">
                                @foreach($selectedPacote->prices as $price)
                                    {{ $price->shortLabel() }}: R$ {{ number_format($price->price, 2, ',', '.') }}
                                    @if(!$loop->last) · @endif
                                @endforeach
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Extras --}}
                <div class="card-artisan p-5 border-l-4 border-l-olive">
                    <h4 class="font-extrabold text-brown-dark flex items-center gap-2 mb-3">
                        <span>➕</span> Extras
                    </h4>
                    @if($extras->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($extras as $extra)
                                @php $isExtraSelected = in_array($extra->id, $extraIds); @endphp
                                <label class="flex items-center gap-3 cursor-pointer p-2 rounded-lg hover:bg-cream transition-colors">
                                    <input
                                        type="checkbox"
                                        wire:change="toggleExtra({{ $extra->id }})"
                                        @checked($isExtraSelected)
                                        class="w-5 h-5 rounded accent-terracotta"
                                    >
                                    <div class="flex-1">
                                        <span class="font-bold text-brown-dark text-sm">{{ $extra->name }}</span>
                                        @if($extra->prices->isNotEmpty())
                                            <span class="text-terracotta font-bold text-sm ml-2">
                                                R$ {{ number_format($extra->prices->first()->price, 2, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($isExtraSelected)
                                        <span class="text-xs text-olive-darker font-bold bg-olive-lighter px-2 py-0.5 rounded-full">✅</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="text-brown-lighter text-sm">Nenhum extra cadastrado.</p>
                    @endif
                </div>

                @if($refeicoes->isEmpty() && $saladas->isEmpty() && $pacotes->isEmpty() && $extras->isEmpty())
                    <div class="text-center py-8 text-brown-lighter">
                        <p class="text-4xl mb-3">📭</p>
                        <p class="font-bold">Nenhum produto cadastrado.</p>
                        <p class="text-sm">Cadastre produtos antes de montar o cardápio.</p>
                    </div>
                @endif
            </div>

            {{-- Ações --}}
            <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-cream-darker">
                <button wire:click="cancelForm" class="btn-ghost text-sm">
                    Cancelar
                </button>
                <button wire:click="saveMenu" class="btn-terracotta text-sm">
                    💾 {{ $editingMenu ? 'Atualizar Cardápio' : 'Criar Cardápio' }}
                </button>
            </div>
        </div>
    @endif

    {{-- ===== LISTA DE MENUS ===== --}}
    @if(!$showForm)
        <div class="space-y-4">
            @forelse($menus as $menu)
                @php
                    $statusColors = [
                        'planejamento' => 'border-l-olive bg-olive-lighter/30',
                        'aberto'       => 'border-l-green-500 bg-green-50/50',
                        'encerrado'    => 'border-l-brown-lighter bg-cream-dark/50',
                    ];
                    $statusLabels = [
                        'planejamento' => '📋 Planejamento',
                        'aberto'       => '✅ Aberto',
                        'encerrado'    => '🔒 Encerrado',
                    ];
                    $color = $statusColors[$menu->status] ?? $statusColors['encerrado'];
                @endphp

                <div class="card-artisan border-l-4 {{ $color }} p-5">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        {{-- Info --}}
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <h3 class="font-extrabold text-brown-dark text-lg">
                                    {{ $menu->start_date->format('d/m') }} — {{ $menu->end_date->format('d/m/Y') }}
                                </h3>
                                <span class="text-xs px-3 py-0.5 rounded-full font-bold {{ match($menu->status) {
                                    'planejamento' => 'bg-olive-lighter text-olive-darker',
                                    'aberto'       => 'bg-green-100 text-green-700',
                                    'encerrado'    => 'bg-brown-lighter/20 text-brown-light',
                                    default        => 'bg-gray-100 text-gray-500'
                                } }}">
                                    {{ $statusLabels[$menu->status] ?? $menu->status }}
                                </span>
                            </div>

                            <div class="flex gap-4 text-xs text-brown-light mt-1">
                                <span>🍽️ {{ $menu->products_count }} produtos</span>
                                <span>📦 {{ $menu->orders_count }} pedidos</span>
                            </div>
                        </div>

                        {{-- Ações --}}
                        <div class="flex gap-2 flex-wrap">
                            <button wire:click="openEditForm({{ $menu->id }})" class="btn-outline text-xs py-1.5 px-3">
                                ✏️ Editar
                            </button>

                            @if($menu->status === 'planejamento')
                                <button wire:click="updateStatus({{ $menu->id }}, 'aberto')" class="btn-olive text-xs py-1.5 px-3">
                                    ▶️ Abrir
                                </button>
                            @elseif($menu->status === 'aberto')
                                <button wire:click="updateStatus({{ $menu->id }}, 'encerrado')" class="btn-ghost text-xs py-1.5 px-3">
                                    🔒 Encerrar
                                </button>
                            @elseif($menu->status === 'encerrado')
                                <button wire:click="updateStatus({{ $menu->id }}, 'aberto')" class="btn-olive text-xs py-1.5 px-3">
                                    🔓 Reabrir
                                </button>
                            @endif

                            @if($menu->orders_count === 0)
                                <button wire:click="confirmDelete({{ $menu->id }})" class="btn-ghost text-xs py-1.5 px-3 text-red-500 hover:text-red-700">
                                    🗑️
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-16 fade-in">
                    <div class="text-6xl mb-6">📅</div>
                    <h2 class="title-hand text-3xl text-brown-dark mb-3">Nenhum cardápio ainda</h2>
                    <p class="text-brown-light max-w-md mx-auto mb-6">
                        Crie o primeiro cardápio semanal para começar a receber pedidos!
                    </p>
                    <button wire:click="openCreateForm" class="btn-terracotta">
                        + Criar Primeiro Cardápio
                    </button>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ===== MODAL DE CONFIRMAÇÃO DE EXCLUSÃO ===== --}}
    @if($showDeleteConfirm)
        <div class="fixed inset-0 bg-brown-dark bg-opacity-40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
             x-data x-init>
            <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl fade-in">
                <div class="text-center">
                    <p class="text-4xl mb-4">🗑️</p>
                    <h3 class="font-extrabold text-brown-dark text-lg mb-2">Excluir Cardápio?</h3>
                    <p class="text-brown-light text-sm mb-6">
                        Esta ação não pode ser desfeita. O cardápio e seus vínculos com produtos serão removidos.
                    </p>
                    <div class="flex gap-3 justify-center">
                        <button wire:click="cancelDelete" class="btn-ghost text-sm">Cancelar</button>
                        <button wire:click="deleteMenu" class="btn-terracotta text-sm">Sim, Excluir</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
