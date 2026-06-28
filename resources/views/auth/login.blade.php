<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabor de Mãe — Login</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍲</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-linen min-h-screen flex items-center justify-center p-4 font-body">

    <div class="w-full max-w-md">
        {{-- Card de Login --}}
        <div class="card-artisan bg-white p-8 md:p-10">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-terracotta to-terracotta-dark shadow-lg mb-4">
                    <span class="text-4xl">🍲</span>
                </div>
                <h1 class="title-hand text-4xl md:text-5xl text-brown-dark mb-2">Sabor de Mãe</h1>
                <p class="text-brown-light">Faça login para continuar</p>
            </div>

            {{-- Erros --}}
            @if($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-xl mb-6 text-sm font-bold">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Formulario --}}
            <form method="POST" action="/login" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-extrabold text-brown-dark mb-2">Email</label>
                    <input
                        type="email"
                        name="email"
                        class="input-warm"
                        placeholder="seu@email.com"
                        required
                        autofocus
                    >
                </div>

                <div>
                    <label class="block text-sm font-extrabold text-brown-dark mb-2">Senha</label>
                    <input
                        type="password"
                        name="password"
                        class="input-warm"
                        placeholder="Sua senha"
                        required
                    >
                </div>

                <button type="submit" class="btn-terracotta w-full text-lg py-4">
                    Entrar
                </button>
            </form>

            {{-- Dica --}}
            <div class="mt-6 pt-4 border-t border-cream-darker text-center">
                <p class="text-xs text-brown-lighter">
                    Ambiente de testes — use os dados do seed
                </p>
            </div>
        </div>

        {{-- Voltar --}}
        <p class="text-center mt-6">
            <a href="/" class="btn-ghost text-sm text-brown-light" wire:navigate>
                ← Voltar ao Cardápio
            </a>
        </p>
    </div>
</body>
</html>
