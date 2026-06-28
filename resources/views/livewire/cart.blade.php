<div x-data="{ open: false }">
    {{-- Botao Flutuante do Carrinho --}}
    <button
        @click="open = !open"
        class="fixed bottom-6 right-6 w-16 h-16 rounded-full flex items-center justify-center shadow-lg z-50 transition-all duration-300 hover:scale-110"
        style="background: linear-gradient(135deg, var(--color-terracotta), var(--color-terracotta-dark));"
        aria-label="Abrir carrinho"
    >
        <span class="text-white text-xl font-extrabold">{{ count($cart) }}</span>
        <span class="absolute -top-1 -right-1 text-sm">🛒</span>
    </button>

    {{-- Overlay do Carrinho --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed bottom-24 right-6 w-96 max-w-[92vw] bg-white rounded-2xl shadow-2xl p-0 z-50 border-2 border-cream-darker overflow-hidden"
        style="display: none;"
    >
        {{-- Cabecalho --}}
        <div class="bg-gradient-to-r from-terracotta-lighter to-olive-lighter px-5 py-4 flex justify-between items-center border-b border-cream-darker">
            <h3 class="text-lg font-extrabold text-brown-dark flex items-center gap-2">
                <span>🛒</span> Seu Carrinho
            </h3>
            <button
                @click="open = false"
                class="text-brown-light hover:text-brown-dark text-xl leading-none transition-colors"
                aria-label="Fechar carrinho"
            >
                ✕
            </button>
        </div>

        <div class="p-5">
            @if(empty($cart))
                <div class="text-center py-8">
                    <p class="text-5xl mb-3">🍲</p>
                    <p class="text-brown-light">Seu carrinho está vazio.</p>
                    <p class="text-brown-lighter text-sm mt-1">Adicione marmitas do cardápio!</p>
                </div>
            @else
                {{-- Itens --}}
                <div class="space-y-3 mb-4 max-h-56 overflow-y-auto pr-1">
                    @foreach($cart as $id => $item)
                        <div class="flex justify-between items-center bg-cream rounded-xl p-3 border border-cream-darker">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-brown-dark text-sm truncate">{{ $item['name'] }}</p>
                                <p class="text-xs text-brown-light">
                                    {{ $item['quantity'] }}x R$ {{ number_format($item['price'], 2, ',', '.') }}
                                </p>
                            </div>
                            <p class="font-extrabold text-terracotta ml-3 text-sm whitespace-nowrap">
                                R$ {{ number_format($item['price'] * $item['quantity'], 2, ',', '.') }}
                            </p>
                        </div>
                    @endforeach
                </div>

                {{-- Endereço de Entrega --}}
                @if(Auth::check() && count($userAddresses) > 0)
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-brown-dark mb-2">
                            📍 Endereço de Entrega
                        </label>
                        <select wire:model.live="selectedAddressId" class="input-warm text-sm">
                            <option value="">Selecione...</option>
                            @foreach($userAddresses as $addr)
                                <option value="{{ $addr['id'] }}">
                                    {{ $addr['street'] }}, {{ $addr['number'] }}
                                    — {{ $addr['neighborhood'] }}
                                    @if($addr['is_default']) ⭐ @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                @elseif(!empty($cart))
                    <p class="text-xs text-brown-lighter text-center mb-3">
                        📍 Você poderá configurar o endereço de entrega no checkout.
                    </p>
                @endif

                {{-- Totais --}}
                <div class="bg-cream-dark rounded-xl p-4 space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-brown-light">Subtotal</span>
                        <span class="font-bold text-brown-dark">R$ {{ number_format($this->subtotal, 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-brown-light">Taxa de Entrega</span>
                        <span class="font-bold text-brown-dark">
                            @if($deliveryFee > 0)
                                R$ {{ number_format($deliveryFee, 2, ',', '.') }}
                            @else
                                <span class="text-olive-dark">Grátis</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between text-lg pt-2 border-t-2 border-cream-darker">
                        <span class="font-extrabold text-brown-dark">Total</span>
                        <span class="font-extrabold text-terracotta">R$ {{ number_format($this->total, 2, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Botao Checkout --}}
                <button
                    wire:click="goToCheckout"
                    @click="open = false"
                    class="btn-terracotta w-full"
                >
                    Ir para o Checkout ✨
                </button>
            @endif
        </div>
    </div>
</div>
