<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabor de Mãe — Painel da Mãe</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍲</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;600;700&family=Nunito:ital,wght@0,300;0,400;0,600;0,700;0,800;1,400&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-cream min-h-screen font-body text-brown">

    {{-- Admin Navbar --}}
    <nav class="navbar-warm">
        <div class="container mx-auto px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3">
            {{-- Logo --}}
            <a href="/admin" class="flex items-center gap-3 group shrink-0" wire:navigate>
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-olive to-olive-dark flex items-center justify-center shadow-md">
                    <span class="text-xl">👩‍🍳</span>
                </div>
                <div class="flex flex-col">
                    <span class="title-hand text-xl text-brown-dark leading-tight">Sabor de Mãe</span>
                    <span class="text-xs text-brown-light tracking-wide">Painel Admin</span>
                </div>
            </a>

            {{-- Links Admin --}}
            <div class="flex gap-1 flex-wrap justify-center">
                <a href="/admin"
                   class="btn-ghost text-sm {{ request()->is('admin') && !request()->is('admin/*') ? 'text-terracotta font-bold' : '' }}">
                    📊 Dashboard
                </a>
                <a href="/admin/lista-compras"
                   class="btn-ghost text-sm {{ request()->is('admin/lista-compras') ? 'text-terracotta font-bold' : '' }}">
                    📋 Lista de Compras
                </a>
                <a href="/admin/entregas"
                   class="btn-ghost text-sm {{ request()->is('admin/entregas') ? 'text-terracotta font-bold' : '' }}">
                    🚗 Entregas
                </a>
                <a href="/admin/cardapios"
                   class="btn-ghost text-sm {{ request()->is('admin/cardapios') ? 'text-terracotta font-bold' : '' }}">
                    📅 Cardápios
                </a>
                <a href="/admin/produtos"
                   class="btn-ghost text-sm {{ request()->is('admin/produtos') ? 'text-terracotta font-bold' : '' }}">
                    🍽️ Produtos
                </a>
                <span class="border-l border-cream-darker mx-1 hidden sm:inline"></span>
                <a href="/" class="btn-ghost text-sm" wire:navigate>🏠 Ver Site</a>
                <form method="POST" action="/logout" class="inline">
                    @csrf
                    <button type="submit" class="btn-ghost text-sm">🚪 Sair</button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Conteudo Admin --}}
    <main class="container mx-auto py-8 px-4">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
