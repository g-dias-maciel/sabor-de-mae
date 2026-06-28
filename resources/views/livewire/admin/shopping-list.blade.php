<div class="fade-in">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
            <h1 class="title-hand text-4xl md:text-5xl text-brown-dark mb-1">Lista de Compras</h1>
            <p class="text-brown-light">Produção Semanal — saiba o que cozinhar</p>
        </div>
        <button
            onclick="window.print()"
            class="btn-olive print:hidden text-sm"
        >
            🖨️ Imprimir Lista
        </button>
    </div>

    @if($this->produtosAgregados->isEmpty())
        <div class="text-center py-16">
            <p class="text-6xl mb-4">📋</p>
            <p class="text-2xl text-brown-light">Nenhum pedido confirmado para esta semana.</p>
        </div>
    @else
        {{-- Destaque Total --}}
        <div class="card-artisan mb-8">
            <div class="p-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-terracotta-lighter flex items-center justify-center text-3xl">
                        🏭
                    </div>
                    <div>
                        <p class="text-sm text-brown-light uppercase tracking-wide font-bold">Produzir esta semana</p>
                        <p class="text-5xl font-extrabold text-terracotta">{{ $this->totalGeral }} <span class="text-2xl text-brown-light">marmitas</span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lista de Produtos --}}
        <div class="space-y-4">
            @foreach($this->produtosAgregados as $agregado)
                <div class="card-artisan">
                    <div class="p-5 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">
                                {{ match($loop->index % 6) { 0 => '🍗', 1 => '🥩', 2 => '🍄', 3 => '🥗', 4 => '🥦', 5 => '🍖', default => '🍲' } }}
                            </span>
                            <div>
                                <h3 class="text-xl font-bold text-brown-dark">{{ $agregado->product->name }}</h3>
                                @if($agregado->size)
                                    <span class="text-xs font-bold text-terracotta uppercase">
                                        🥡 {{ $agregado->size === 'G' ? 'Grande (750g)' : ($agregado->size === 'P' ? 'Pequena (500g)' : 'Tamanho Único') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-extrabold text-terracotta">{{ $agregado->total_quantidade }}</span>
                            <span class="text-lg text-brown-light">unidades</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- CSS para impressao --}}
        <style>
            @media print {
                body { font-size: 16pt; }
                nav, .print\:hidden { display: none !important; }
                .card-artisan { box-shadow: none !important; border: 2px solid #000 !important; }
                .card-artisan::before { display: none; }
            }
        </style>
    @endif
</div>
