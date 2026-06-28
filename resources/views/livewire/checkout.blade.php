<div class="container mx-auto px-4 py-8 max-w-2xl fade-in" x-data="{ authTab: '{{ $authMode }}' }">

    {{-- Mensagens --}}
    @if($errorMessage)
        <div
            class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-xl mb-6 shadow-sm"
            x-data="{ show: true }"
            x-show="show"
            x-transition
            x-init="setTimeout(() => show = false, 6000)"
        >
            <p class="font-bold flex items-center gap-2">⚠️ {{ $errorMessage }}</p>
        </div>
    @endif

    @if($successMessage)
        <div
            class="bg-olive-lighter border-l-4 border-olive text-olive-darker p-4 rounded-r-xl mb-6 shadow-sm"
            x-data="{ show: true }"
            x-show="show"
            x-transition
        >
            <p class="font-bold flex items-center gap-2">✅ {{ $successMessage }}</p>
        </div>
    @endif

    <h1 class="title-hand text-4xl md:text-5xl text-brown-dark mb-8 text-center">Finalizar Pedido</h1>

    {{-- ===== Seamless Auth ===== --}}
    @if(!$isAuthenticated && !Auth::check())
        <div class="card-artisan mb-8">
            <div class="p-6">
                <h2 class="text-xl font-extrabold text-brown-dark mb-4 flex items-center gap-2">
                    <span>👤</span> Identificação
                </h2>

                {{-- Tabs --}}
                <div class="flex border-b border-cream-darker mb-4">
                    <button
                        @click="authTab = 'login'; $wire.set('authMode', 'login')"
                        :class="authTab === 'login'
                            ? 'border-terracotta text-terracotta font-bold'
                            : 'border-transparent text-brown-light hover:text-brown-dark'"
                        class="px-4 py-2 border-b-2 transition-colors text-sm"
                    >
                        Entrar
                    </button>
                    <button
                        @click="authTab = 'register'; $wire.set('authMode', 'register')"
                        :class="authTab === 'register'
                            ? 'border-terracotta text-terracotta font-bold'
                            : 'border-transparent text-brown-light hover:text-brown-dark'"
                        class="px-4 py-2 border-b-2 transition-colors text-sm"
                    >
                        Criar Conta
                    </button>
                </div>

                {{-- Login Form --}}
                <div x-show="authTab === 'login'" class="space-y-3">
                    <div>
                        <label class="block text-sm font-bold text-brown-dark mb-1">E-mail</label>
                        <input type="email" wire:model="loginEmail" class="input-warm" placeholder="seu@email.com">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-brown-dark mb-1">Senha</label>
                        <input type="password" wire:model="loginPassword" class="input-warm" placeholder="Sua senha">
                    </div>
                    <button wire:click="login" class="btn-terracotta w-full text-sm">
                        Entrar
                    </button>
                </div>

                {{-- Register Form --}}
                <div x-show="authTab === 'register'" class="space-y-3">
                    <div>
                        <label class="block text-sm font-bold text-brown-dark mb-1">Nome</label>
                        <input type="text" wire:model="registerName" class="input-warm" placeholder="Seu nome completo">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-bold text-brown-dark mb-1">E-mail</label>
                            <input type="email" wire:model="registerEmail" class="input-warm" placeholder="seu@email.com">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-brown-dark mb-1">Telefone</label>
                            <input type="text" wire:model="registerPhone" class="input-warm" placeholder="(11) 99999-0000">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-brown-dark mb-1">Senha</label>
                        <input type="password" wire:model="registerPassword" class="input-warm" placeholder="Mínimo 6 caracteres">
                    </div>
                    <button wire:click="register" class="btn-terracotta w-full text-sm">
                        Criar Conta e Continuar
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if(empty($cart))
        {{-- Carrinho Vazio --}}
        <div class="text-center py-16">
            <p class="text-6xl mb-4">🛒</p>
            <p class="text-xl text-brown-light mb-6">Seu carrinho está vazio.</p>
            <a href="/" class="btn-terracotta inline-flex" wire:navigate>
                ← Voltar ao Cardápio
            </a>
        </div>
    @else
        {{-- ===== Resumo do Pedido ===== --}}
        <div class="card-artisan mb-6">
            <div class="p-6">
                <h2 class="text-xl font-extrabold text-brown-dark mb-4 flex items-center gap-2">
                    <span>📦</span> Resumo do Pedido
                </h2>
                <div class="space-y-2">
                    @foreach($cart as $item)
                        <div class="flex justify-between items-center py-3 border-b border-cream-darker last:border-0">
                            <div>
                                <span class="font-bold text-brown-dark">{{ $item['name'] }}</span>
                                <span class="text-brown-light text-sm ml-2">x{{ $item['quantity'] }}</span>
                            </div>
                            <span class="font-bold text-brown-dark">
                                R$ {{ number_format($item['price'] * $item['quantity'], 2, ',', '.') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Endereço Estruturado ===== --}}
        <div class="card-artisan mb-6">
            <div class="p-6">
                <h2 class="text-xl font-extrabold text-brown-dark mb-4 flex items-center gap-2">
                    <span>📍</span> Endereço de Entrega
                </h2>

                {{-- Dropdown de Endereços Salvos --}}
                @if(count($userAddresses) > 0)
                    <div class="mb-4 p-4 bg-cream rounded-xl border border-cream-darker">
                        <label class="block text-sm font-bold text-brown-dark mb-2">
                            📋 Seus Endereços
                        </label>
                        <select wire:model.live="selectedAddressId" class="input-warm">
                            <option value="">Digitar novo endereço...</option>
                            @foreach($userAddresses as $addr)
                                <option value="{{ $addr['id'] }}">
                                    {{ $addr['street'] }}, {{ $addr['number'] }}
                                    @if($addr['complement']) — {{ $addr['complement'] }} @endif
                                    — {{ $addr['neighborhood'] }}
                                    @if($addr['is_default']) ⭐ @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-brown-dark mb-1">Rua</label>
                            <input type="text" wire:model="street" class="input-warm"
                                   placeholder="Rua das Flores">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-brown-dark mb-1">Número</label>
                            <input type="text" wire:model="number" class="input-warm"
                                   placeholder="123">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-brown-dark mb-1">Complemento</label>
                        <input type="text" wire:model="complement" class="input-warm"
                               placeholder="Apto 42, Bloco B (opcional)">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-bold text-brown-dark mb-1">Bairro</label>
                            <select wire:model.live="neighborhood" class="input-warm">
                                <option value="">Selecione...</option>
                                @foreach($neighborhoodOptions as $bairro)
                                    <option value="{{ $bairro }}">{{ $bairro }}</option>
                                @endforeach
                            </select>
                            @if($deliveryZoneId)
                                <p class="text-xs text-olive-dark mt-1">✅ Bairro atendido!</p>
                            @elseif($neighborhood)
                                <p class="text-xs text-red-500 mt-1">⚠️ Bairro não atendido</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-brown-dark mb-1">CEP</label>
                            <input type="text" wire:model="zipCode" class="input-warm"
                                   placeholder="00000-000">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-brown-dark mb-1">Cidade</label>
                        <input type="text" wire:model="city" class="input-warm"
                               placeholder="São Paulo">
                    </div>

                    @if($isAuthenticated || Auth::check())
                        @if($selectedAddressId)
                            <p class="text-sm text-olive-dark flex items-center gap-1">
                                ✅ Usando endereço salvo —
                                <button type="button" wire:click="$set('selectedAddressId', null)" class="underline text-terracotta hover:text-terracotta-dark">
                                    editar manualmente
                                </button>
                            </p>
                        @else
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="saveAddress" class="w-4 h-4 accent-terracotta">
                                <span class="text-sm text-brown-light">Salvar como endereço padrão</span>
                            </label>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== Pagamento ===== --}}
        <div class="card-artisan mb-8">
            <div class="p-6">
                <h3 class="text-xl font-extrabold text-brown-dark mb-4 flex items-center gap-2">
                    <span>💳</span> Forma de Pagamento
                </h3>

                <div class="space-y-3">
                    <label class="flex items-center gap-4 p-4 rounded-xl cursor-pointer transition-all border-2 border-cream-darker hover:border-terracotta-light"
                           :class="$wire.paymentMethod === 'pix' ? '!border-terracotta bg-terracotta-lighter' : ''">
                        <input type="radio" wire:model="paymentMethod" value="pix" class="w-5 h-5 accent-terracotta">
                        <div>
                            <span class="text-lg font-bold text-brown-dark">💠 Pix</span>
                            <p class="text-sm text-brown-light">Pagamento instantâneo via código Pix</p>
                        </div>
                    </label>

                    <!-- <label class="flex items-center gap-4 p-4 rounded-xl cursor-pointer transition-all border-2 border-cream-darker hover:border-terracotta-light"
                           :class="$wire.paymentMethod === 'dinheiro' ? '!border-terracotta bg-terracotta-lighter' : ''">
                        <input type="radio" wire:model="paymentMethod" value="dinheiro" class="w-5 h-5 accent-terracotta">
                        <div>
                            <span class="text-lg font-bold text-brown-dark">💵 Pagar na Entrega</span>
                            <p class="text-sm text-brown-light">Pague em dinheiro quando receber</p>
                        </div>
                    </label> -->
                </div>
            </div>
        </div>

        {{-- Botao Confirmar --}}
        <button
            wire:click="processCheckout"
            class="btn-terracotta w-full text-xl py-4"
        >
            Confirmar Pedido ✨
        </button>
    @endif

    {{-- Modal Pix --}}
    <div
        x-show="$wire.showPixModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-brown-dark bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        style="display: none;"
        {{-- Polling para verificar pagamento a cada 3 segundos --}}
        wire:poll.3s="checkPaymentStatus"
    >
        <div
            class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl border-2 border-cream-darker"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-data="{
                copyText: '{{ $pixCopyPaste }}',
                copied: false,
                async copiar() {
                    try {
                        await navigator.clipboard.writeText(this.copyText);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    } catch {
                        // fallback para mobile/navegadores antigos
                        const ta = document.createElement('textarea');
                        ta.value = this.copyText;
                        ta.style.position = 'fixed';
                        ta.style.opacity = '0';
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand('copy');
                        document.body.removeChild(ta);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    }
                }
            }"
        >
            <h3 class="text-2xl font-extrabold text-brown-dark text-center mb-4 flex items-center justify-center gap-2">
                <span>💠</span> Pagamento Pix
            </h3>
            <p class="text-brown-light text-center mb-6">
                Escaneie o QR Code ou copie o código abaixo:
            </p>

            {{-- QR Code Real --}}
            <div class="bg-cream p-6 rounded-xl mb-4 text-center border-2 border-cream-darker">
                @if($pixQrCodeBase64)
                    <img src="data:image/png;base64,{{ $pixQrCodeBase64 }}"
                         alt="QR Code PIX"
                         class="w-48 h-48 mx-auto rounded-lg">
                @else
                    <div class="w-48 h-48 bg-white mx-auto border-2 border-cream-darker rounded-lg mb-2 flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-5xl mb-2">📱</p>
                            <p class="text-brown-lighter text-xs">QR Code Pix</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Codigo Pix --}}
            <div class="bg-cream-dark p-4 rounded-xl mb-6 border border-cream-darker">
                <p class="text-xs break-all text-brown-light font-mono mb-3">{{ $pixCopyPaste }}</p>
                <button
                    x-on:click="copiar"
                    class="btn-outline text-sm w-full transition-colors"
                    :class="copied ? '!bg-olive !text-white !border-olive' : ''"
                >
                    <span x-show="!copied">📋 Copiar Código Pix</span>
                    <span x-show="copied">✅ Copiado!</span>
                </button>
            </div>

            <div class="text-center text-brown-lighter text-xs mb-4">
                <span>⏳ Aguardando pagamento...</span>
            </div>

            <button
                wire:click="closePixModal"
                class="btn-terracotta w-full"
            >
                Já paguei! ✅
            </button>
        </div>
    </div>
</div>
