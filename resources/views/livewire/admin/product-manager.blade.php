<div class="fade-in" x-data="{ showMsg: @json($message ? true : false) }">
    {{-- Cabeçalho --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="title-hand text-3xl md:text-4xl text-brown-dark">🍽️ Produtos</h1>
            <p class="text-brown-light text-sm mt-1">Gerencie os pratos, saladas, pacotes e extras</p>
        </div>
        @if(!$showForm)
            <button wire:click="openCreateForm" class="btn-terracotta text-sm">
                + Novo Produto
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

    {{-- ===== FORMULÁRIO ===== --}}
    @if($showForm)
        <div class="card-artisan p-6 mb-8">
            <h2 class="title-hand text-2xl text-brown-dark mb-6">
                {{ $editingProductId ? '✏️ Editar Produto' : '🆕 Novo Produto' }}
            </h2>

            {{-- Dados básicos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <div>
                    <label class="block text-sm font-extrabold text-brown-dark mb-2">Nome do Produto</label>
                    <input type="text" wire:model="productName" class="input-warm w-full" placeholder="Ex: Carne de Panela">
                    @error('productName') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-extrabold text-brown-dark mb-2">Tipo</label>
                    <select wire:model="productType" class="input-warm w-full">
                        <option value="refeicao">🍽️ Refeição</option>
                        <option value="salada">🥗 Salada</option>
                        <option value="pacote_semanal">⭐ Pacote Semanal</option>
                        <option value="extra">➕ Extra</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-extrabold text-brown-dark mb-2">Descrição</label>
                    <textarea wire:model="productDescription" rows="3" class="input-warm w-full" placeholder="Descreva o prato, acompanhamentos..."></textarea>
                </div>

                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model="productAvailable" class="w-5 h-5 rounded accent-terracotta">
                        <span class="text-sm font-extrabold text-brown-dark">Produto disponível</span>
                    </label>
                </div>
            </div>

            {{-- Preços --}}
            <div class="border-t border-cream-darker pt-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-extrabold text-brown-dark flex items-center gap-2">
                        <span>💰</span> Preços
                    </h3>
                    <button wire:click="addPrice" class="btn-outline text-xs py-1 px-3">+ Adicionar Tamanho</button>
                </div>

                @error('prices') <p class="text-red-500 text-xs font-bold mb-3">{{ $message }}</p> @enderror

                <div class="space-y-3">
                    @foreach($prices as $index => $price)
                        <div class="flex items-end gap-3 bg-cream rounded-xl p-4">
                            <div class="w-24">
                                <label class="block text-xs font-bold text-brown-dark mb-1">Tamanho</label>
                                <select wire:model="prices.{{ $index }}.size" class="input-warm w-full text-sm">
                                    <option value="P">P (Pequena)</option>
                                    <option value="M">M (Média/Única)</option>
                                    <option value="G">G (Grande)</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-brown-dark mb-1">Preço (R$)</label>
                                <input type="number" step="0.01" min="0" wire:model="prices.{{ $index }}.price" class="input-warm w-full text-sm" placeholder="19.00">
                            </div>
                            @if(count($prices) > 1)
                                <button wire:click="removePrice({{ $index }})" class="btn-ghost text-red-500 hover:text-red-700 text-sm py-2 px-2 shrink-0">
                                    🗑️
                                </button>
                            @endif
                            @error("prices.{$index}.price")
                                <p class="text-red-500 text-xs font-bold">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Ações --}}
            <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-cream-darker">
                <button wire:click="cancelForm" class="btn-ghost text-sm">Cancelar</button>
                <button wire:click="saveProduct" class="btn-terracotta text-sm">
                    💾 {{ $editingProductId ? 'Atualizar Produto' : 'Criar Produto' }}
                </button>
            </div>
        </div>
    @endif

    {{-- ===== TOP 5 + SELEÇÃO ===== --}}
    @if(!$showForm)
        {{-- Dropdown de seleção rápida --}}
        <div class="card-artisan p-5 mb-6">
            <label class="block text-sm font-extrabold text-brown-dark mb-3 flex items-center gap-2">
                <span>✏️</span> Editar Produto
            </label>
            <select wire:model.live="selectedProductId" class="input-warm w-full text-sm">
                <option value="">Selecione um produto para editar...</option>
                @foreach($allProducts as $prod)
                    @php
                        $typeEmoji = match($prod->type) {
                            'refeicao' => '🍽️',
                            'salada' => '🥗',
                            'pacote_semanal' => '⭐',
                            'extra' => '➕',
                            default => '📦',
                        };
                    @endphp
                    <option value="{{ $prod->id }}">
                        {{ $typeEmoji }} {{ $prod->name }}
                        @if($prod->prices->isNotEmpty())
                            —
                            @foreach($prod->prices as $p)
                                {{ $p->size }}: R$ {{ number_format($p->price, 2, ',', '.') }}
                                @if(!$loop->last) | @endif
                            @endforeach
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Top 5 Produtos Mais Pedidos --}}
        @if($topProducts->isNotEmpty())
            <div class="mb-4">
                <h3 class="font-extrabold text-brown-dark text-lg flex items-center gap-2 mb-3">
                    <span>🌟</span> Top 5 Produtos Mais Pedidos
                </h3>
                <div class="space-y-3">
                    @foreach($topProducts as $product)
                        @php
                            $typeBadge = match($product->type) {
                                'refeicao'        => ['🍽️', 'Refeição', 'bg-terracotta-lighter text-terracotta-darker'],
                                'salada'          => ['🥗', 'Salada', 'bg-green-100 text-green-700'],
                                'pacote_semanal'  => ['⭐', 'Pacote Semanal', 'bg-olive-lighter text-olive-darker'],
                                'extra'           => ['➕', 'Extra', 'bg-olive-lighter text-olive-darker'],
                                default           => ['📦', $product->type, 'bg-gray-100 text-gray-600'],
                            };
                        @endphp
                        <div class="card-artisan p-4 {{ $product->is_available ? '' : 'opacity-60' }}">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <h3 class="font-extrabold text-brown-dark">{{ $product->name }}</h3>
                                        <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ $typeBadge[2] }}">
                                            {{ $typeBadge[0] }} {{ $typeBadge[1] }}
                                        </span>
                                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-bold">
                                            📦 {{ $product->order_items_sum_quantity }} pedidos
                                        </span>
                                        @if(!$product->is_available)
                                            <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-bold">🚫 Indisponível</span>
                                        @endif
                                    </div>
                                    @if($product->description)
                                        <p class="text-xs text-brown-light truncate max-w-lg">{{ Str::limit($product->description, 80) }}</p>
                                    @endif
                                    <p class="text-xs text-terracotta font-bold mt-1">
                                        @foreach($product->prices as $price)
                                            {{ $price->size }}: R$ {{ number_format($price->price, 2, ',', '.') }}
                                            @if(!$loop->last) · @endif
                                        @endforeach
                                    </p>
                                </div>

                                {{-- Ações --}}
                                <div class="flex gap-2 flex-wrap shrink-0">
                                    <button wire:click="toggleAvailable({{ $product->id }})"
                                            class="btn-ghost text-xs py-1.5 px-3"
                                            title="{{ $product->is_available ? 'Marcar indisponível' : 'Marcar disponível' }}">
                                        {{ $product->is_available ? '✅' : '⬜' }}
                                    </button>
                                    <button wire:click="openEditForm({{ $product->id }})" class="btn-outline text-xs py-1.5 px-3">
                                        ✏️ Editar
                                    </button>
                                    @if($product->orderItems()->count() === 0)
                                        <button wire:click="confirmDelete({{ $product->id }})"
                                                class="btn-ghost text-xs py-1.5 px-3 text-red-500 hover:text-red-700">
                                            🗑️
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Demais produtos (não top 5, com pedidos) --}}
        @php
            $topIds = $topProducts->pluck('id')->toArray();
            $otherProducts = $allProducts->reject(fn($p) => in_array($p->id, $topIds));
        @endphp
        @if($otherProducts->isNotEmpty())
            <details class="mt-4">
                <summary class="cursor-pointer text-sm font-extrabold text-brown-lighter hover:text-brown-light transition-colors py-2">
                    📋 Outros Produtos ({{ $otherProducts->count() }})
                </summary>
                <div class="space-y-2 mt-2">
                    @foreach($otherProducts as $product)
                        @php
                            $typeBadge = match($product->type) {
                                'refeicao'        => ['🍽️', 'Refeição', 'bg-terracotta-lighter text-terracotta-darker'],
                                'salada'          => ['🥗', 'Salada', 'bg-green-100 text-green-700'],
                                'pacote_semanal'  => ['⭐', 'Pacote Semanal', 'bg-olive-lighter text-olive-darker'],
                                'extra'           => ['➕', 'Extra', 'bg-olive-lighter text-olive-darker'],
                                default           => ['📦', $product->type, 'bg-gray-100 text-gray-600'],
                            };
                        @endphp
                        <div class="card-artisan p-3 {{ $product->is_available ? '' : 'opacity-60' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-brown-dark text-sm">{{ $product->name }}</span>
                                        <span class="text-xs px-2 py-0.5 rounded-full font-bold {{ $typeBadge[2] }}">
                                            {{ $typeBadge[1] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-1 shrink-0">
                                    <button wire:click="openEditForm({{ $product->id }})" class="btn-ghost text-xs py-1 px-2">✏️</button>
                                    @if($product->orderItems()->count() === 0)
                                        <button wire:click="confirmDelete({{ $product->id }})"
                                                class="btn-ghost text-xs py-1 px-2 text-red-500 hover:text-red-700">🗑️</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif

        {{-- Estado vazio --}}
        @if($allProducts->isEmpty())
            <div class="text-center py-16 fade-in">
                <div class="text-6xl mb-6">🍽️</div>
                <h2 class="title-hand text-3xl text-brown-dark mb-3">Nenhum produto cadastrado</h2>
                <p class="text-brown-light max-w-md mx-auto mb-6">
                    Cadastre os pratos, saladas e extras para começar a montar os cardápios.
                </p>
                <button wire:click="openCreateForm" class="btn-terracotta">
                    + Criar Primeiro Produto
                </button>
            </div>
        @endif
    @endif

    {{-- ===== MODAL DE EXCLUSÃO ===== --}}
    @if($showDeleteConfirm)
        <div class="fixed inset-0 bg-brown-dark bg-opacity-40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
             x-data x-init>
            <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl fade-in">
                <div class="text-center">
                    <p class="text-4xl mb-4">🗑️</p>
                    <h3 class="font-extrabold text-brown-dark text-lg mb-2">Excluir Produto?</h3>
                    <p class="text-brown-light text-sm mb-6">
                        O produto e seus preços serão removidos permanentemente.
                    </p>
                    <div class="flex gap-3 justify-center">
                        <button wire:click="cancelDelete" class="btn-ghost text-sm">Cancelar</button>
                        <button wire:click="deleteProduct" class="btn-terracotta text-sm">Sim, Excluir</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
