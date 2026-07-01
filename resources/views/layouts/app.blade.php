<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabor de Mãe — Marmitas Caseiras</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍲</text></svg>">
    {{-- Google Fonts preconnect + carregamento direto (mais confiável que @import no CSS) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;600;700&family=Nunito:ital,wght@0,300;0,400;0,600;0,700;0,800;1,400&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-linen min-h-screen font-body text-brown antialiased">

    {{-- Navbar --}}
    <nav class="navbar-warm">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            {{-- Logo + Nome --}}
            <a href="/" class="flex items-center gap-3 group" wire:navigate>
                <div class="relative flex-shrink-0">
                    {{-- Circulo Terracotta com icone --}}
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-terracotta to-terracotta-dark flex items-center justify-center shadow-md group-hover:shadow-lg transition-shadow">
                        <span class="text-2xl">🍲</span>
                    </div>
                    {{-- Ramo de folhas decorativo --}}
                    <span class="absolute -top-1 -right-1 text-base leaf-accent">🌿</span>
                </div>
                <div class="flex flex-col">
                    <span class="title-hand text-2xl md:text-3xl text-brown-dark leading-tight">Sabor de Mãe</span>
                    <span class="text-xs text-brown-light tracking-wide hidden sm:block">Marmitas Caseiras</span>
                </div>
            </a>

            {{-- Navegacao --}}
            <div class="flex gap-3 items-center">
                {{-- Carrinho (sempre visivel exceto admin) --}}
                @unless(auth()->check() && auth()->user()->isAdmin())
                    <a href="/checkout"
                       class="btn-outline text-sm py-2 px-4 inline-flex items-center gap-1"
                       wire:navigate>
                        🛒 Carrinho
                    </a>
                @endunless

                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="/admin"
                           class="btn-outline text-sm py-2 px-4 hidden sm:inline-flex"
                           wire:navigate>
                            📋 Painel
                        </a>
                    @else
                        <a href="/perfil"
                           class="btn-outline text-sm py-2 px-4 hidden sm:inline-flex"
                           wire:navigate>
                            👤 Perfil
                        </a>
                        <a href="/meus-pedidos"
                           class="btn-outline text-sm py-2 px-4 hidden sm:inline-flex"
                           wire:navigate>
                            📦 Meus Pedidos
                        </a>
                    @endif

                    <form method="POST" action="/logout" class="inline">
                        @csrf
                        <button type="submit" class="btn-ghost text-sm">
                            Sair
                        </button>
                    </form>
                @else
                    <a href="/login"
                       class="btn-outline text-sm py-2 px-4"
                       wire:navigate>
                        Entrar
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Conteudo Principal --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Carrinho Flutuante (aparece em todas as paginas publicas) --}}
    @unless(request()->is('admin*') || request()->is('checkout*') || request()->is('login*'))
        @livewire('cart')
    @endunless

    {{-- Footer --}}
    <footer class="footer-warm mt-16">
        <div class="container mx-auto px-4 py-10">
            {{-- Divisor de folhas --}}
            <div class="leaf-divider text-2xl">
                <span>🌿</span>
                <span class="text-sm font-body text-brown-lighter tracking-widest uppercase">Feito com amor</span>
                <span>🌿</span>
            </div>

            <div class="text-center space-y-3">
                <p class="title-hand text-3xl text-brown-dark">Sabor de Mãe</p>
                <p class="text-brown-light text-lg max-w-md mx-auto leading-relaxed">
                    Comida caseira feita com carinho, do fogão da mãe direto para a sua mesa.
                </p>

                {{-- Info --}}
                <div class="flex flex-wrap justify-center gap-4 text-sm text-brown-lighter pt-4">
                    <span class="badge-terracotta">📅 Pedidos até Sábado 23:59</span>
                    <span class="badge-terracotta">🚫 Sem entregas no Domingo</span>
                </div>

                <p class="text-xs text-brown-lighter pt-6">
                    © {{ date('Y') }} Sabor de Mãe — Todos os direitos reservados
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts
    <script>
        // Alpine.js helper — mensagens temporizadas
        document.addEventListener('alpine:init', () => {
            Alpine.data('autoHide', (delay = 5000) => ({
                show: true,
                init() {
                    setTimeout(() => this.show = false, delay);
                }
            }));
        });
    </script>
</body>
</html>
