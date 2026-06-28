<div class="container mx-auto px-4 py-8 md:py-12 max-w-2xl fade-in">

    <h1 class="title-hand text-4xl md:text-5xl text-brown-dark mb-8 text-center">Meus Pedidos</h1>

    @if(!$isAuthenticated)
        {{-- Não autenticado --}}
        <div class="text-center py-16">
            <p class="text-6xl mb-4">🔒</p>
            <p class="text-xl text-brown-light mb-4">Faça login para ver seus pedidos.</p>
            <a href="/login" class="btn-terracotta inline-flex" wire:navigate>
                Ir para Login
            </a>
        </div>
    @else
        {{-- Mensagem de sucesso --}}
        @if($successMessage)
            <div
                class="bg-olive-lighter border-l-4 border-olive text-olive-darker p-4 rounded-r-xl mb-6 shadow-sm"
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 5000)"
            >
                <p class="font-bold flex items-center gap-2">🔄 {{ $successMessage }}</p>
            </div>
        @endif

        {{-- ===== Pedido(s) da Semana Atual ===== --}}
        @if($currentOrders->isNotEmpty())
            @foreach($currentOrders as $currentOrder)
                <div class="card-artisan mb-6 border-l-4 {{ $currentOrder->payment_status === 'cancelado' ? 'border-l-brown-lighter opacity-60' : 'border-l-terracotta' }}">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span>📦</span>
                                <h2 class="text-xl font-extrabold text-brown-dark">Pedido Semanal</h2>
                                @if($currentOrders->count() > 1)
                                    <span class="text-sm font-bold text-terracotta">#{{ $currentOrder->id }}</span>
                                @endif
                            </div>
                            <span class="badge-terracotta text-xs">#{{ $currentOrder->id }}</span>
                        </div>

                        <div class="space-y-3">
                            @foreach($currentOrder->items as $item)
                                <div class="flex justify-between items-center py-2 border-b border-cream-darker last:border-0">
                                    <div class="flex-1">
                                        <span class="font-bold text-brown-dark">{{ $item->quantity }}x</span>
                                        <span class="text-brown-light ml-1">{{ $item->product?->name ?? 'Produto' }}</span>
                                        @if($item->size && $item->size !== 'M')
                                            <span class="text-xs text-terracotta font-bold ml-1">({{ $item->size }})</span>
                                        @endif
                                    </div>
                                    <span class="font-bold text-brown-dark text-sm">
                                        R$ {{ number_format($item->price_at_purchase * $item->quantity, 2, ',', '.') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-between items-center mt-4 pt-4 border-t-2 border-cream-darker">
                            <span class="text-sm text-brown-light">
                                {{ $currentOrder->payment_method === 'pix' ? '💠 Pix' : '💵 Dinheiro' }}
                                —
                                @if($currentOrder->payment_status === 'cancelado')
                                    <span class="font-bold text-brown-lighter">Cancelado</span>
                                @elseif(in_array($currentOrder->payment_status, ['pago', 'confirmado']))
                                    <span class="font-bold text-olive-dark">Pago</span>
                                @else
                                    <span class="font-bold text-brown-lighter">Pendente</span>
                                @endif
                            </span>
                            <span class="text-2xl font-extrabold text-terracotta">
                                R$ {{ number_format($currentOrder->total, 2, ',', '.') }}
                            </span>
                        </div>

                        {{-- Ações para pedidos pendentes --}}
                        @if($currentOrder->payment_status === 'pendente')
                            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-cream-darker">
                                @if($currentOrder->payment_method === 'pix' && $currentOrder->pix_copy_paste)
                                    <button
                                        wire:click="payPixOrder({{ $currentOrder->id }})"
                                        class="btn-terracotta text-sm flex-1 min-w-[140px]"
                                    >
                                        💠 Pagar com PIX
                                    </button>
                                @endif
                                <button
                                    wire:click="editOrder({{ $currentOrder->id }})"
                                    class="btn-outline text-sm flex-1 min-w-[100px]"
                                >
                                    ✏️ Editar
                                </button>
                                <button
                                    wire:click="cancelOrder({{ $currentOrder->id }})"
                                    wire:confirm="Tem certeza que deseja cancelar este pedido?"
                                    class="text-sm px-4 py-2 rounded-xl border-2 border-red-200 text-red-600 hover:bg-red-50 hover:border-red-400 transition-all flex-1 min-w-[100px]"
                                >
                                    🗑️ Cancelar
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="card-artisan mb-8 text-center">
                <div class="p-8">
                    <p class="text-5xl mb-3">🍲</p>
                    <p class="text-brown-light text-lg">Você ainda não fez pedido esta semana.</p>
                    <a href="/" class="btn-terracotta mt-4 inline-flex text-sm" wire:navigate>
                        Ver Cardápio
                    </a>
                </div>
            </div>
        @endif

        {{-- ===== Histórico de Pedidos ===== --}}
        @if($orderHistory->isNotEmpty())
            <h2 class="title-hand text-3xl text-brown-dark mb-4 flex items-center gap-2">
                <span>📚</span> Histórico
            </h2>

            <div class="space-y-4">
                @foreach($orderHistory as $order)
                    <div class="card-artisan {{ $order->payment_status === 'cancelado' ? 'opacity-60' : '' }}">
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <span class="font-extrabold text-brown-dark">Pedido #{{ $order->id }}</span>
                                    <span class="text-brown-lighter text-sm ml-2">
                                        {{ $order->menu?->start_date?->format('d/m') ?? '—' }}
                                    </span>
                                    @if($order->payment_status === 'cancelado')
                                        <span class="text-xs text-brown-lighter ml-2">(Cancelado)</span>
                                    @endif
                                </div>
                                <span class="text-lg font-extrabold text-brown-dark">
                                    R$ {{ number_format($order->total, 2, ',', '.') }}
                                </span>
                            </div>

                            <div class="text-sm text-brown-light mb-3">
                                @foreach($order->items as $item)
                                    <div class="flex justify-between">
                                        <span>{{ $item->quantity }}x {{ $item->product?->name ?? 'Produto' }}
                                            @if($item->size && $item->size !== 'M')
                                                ({{ $item->size }})
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            @if($order->payment_status === 'pendente')
                                <div class="flex flex-wrap gap-2">
                                    @if($order->payment_method === 'pix' && $order->pix_copy_paste)
                                        <button
                                            wire:click="payPixOrder({{ $order->id }})"
                                            class="btn-terracotta text-sm flex-1 min-w-[140px]"
                                        >
                                            💠 Pagar com PIX
                                        </button>
                                    @endif
                                    <button
                                        wire:click="editOrder({{ $order->id }})"
                                        class="btn-outline text-sm flex-1 min-w-[100px]"
                                    >
                                        ✏️ Editar
                                    </button>
                                    <button
                                        wire:confirm="Cancelar este pedido?"
                                        wire:click="cancelOrder({{ $order->id }})"
                                        class="text-sm px-4 py-2 rounded-xl border-2 border-red-200 text-red-600 hover:bg-red-50 transition-all"
                                    >
                                        🗑️ Cancelar
                                    </button>
                                </div>
                            @else
                                <button
                                    wire:click="repeatOrder({{ $order->id }})"
                                    class="btn-outline text-sm w-full"
                                >
                                    🔄 Repetir este Pedido
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($currentOrders->isEmpty())
            <div class="text-center py-12">
                <p class="text-5xl mb-4">📋</p>
                <p class="text-brown-light text-lg">Nenhum pedido encontrado no histórico.</p>
            </div>
        @endif
    @endif

    {{-- ===== Modal PIX ===== --}}
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
        wire:poll.3s="checkPixPayment"
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

            {{-- QR Code --}}
            <div class="bg-cream p-6 rounded-xl mb-4 text-center border-2 border-cream-darker">
                @if($pixQrCodeBase64)
                    <img src="data:image/png;base64,{{ $pixQrCodeBase64 }}"
                         alt="QR Code PIX"
                         class="w-48 h-48 mx-auto rounded-lg">
                @else
                    <div class="w-48 h-48 bg-white mx-auto border-2 border-cream-darker rounded-lg flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-5xl mb-2">📱</p>
                            <p class="text-brown-lighter text-xs">QR Code Pix</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Cópia do código --}}
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
                Fechar ✅
            </button>
        </div>
    </div>
</div>
