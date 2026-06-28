<div class="min-h-screen bg-linen flex items-center justify-center p-4 font-body">
    <div class="w-full max-w-md">
        {{-- Card Principal --}}
        <div class="card-artisan bg-white p-8 md:p-10">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br from-terracotta to-terracotta-dark shadow-lg mb-4">
                    <span class="text-4xl">🍲</span>
                </div>
                <h1 class="title-hand text-4xl md:text-5xl text-brown-dark mb-2">Sabor de Mãe</h1>
                <p class="text-brown-light">
                    {{ $mode === 'login' ? 'Faça login para continuar' : 'Crie sua conta gratuitamente' }}
                </p>
            </div>

            {{-- Mensagem de Sucesso --}}
            @if($successMessage)
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 4000)"
                    class="bg-olive-lighter border-l-4 border-olive text-olive-darker p-4 rounded-r-xl mb-6 text-sm font-bold"
                >
                    {{ $successMessage }}
                </div>
            @endif

            {{-- Mensagem de Erro --}}
            @if($errorMessage)
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-xl mb-6 text-sm font-bold"
                >
                    {{ $errorMessage }}
                </div>
            @endif

            {{-- Tabs --}}
            <div class="flex mb-6 bg-cream rounded-xl p-1">
                <button
                    wire:click="switchToLogin"
                    class="flex-1 py-2.5 rounded-lg text-sm font-extrabold transition-all
                        {{ $mode === 'login'
                            ? 'bg-white text-brown-dark shadow-sm'
                            : 'text-brown-lighter hover:text-brown-light' }}"
                >
                    🔑 Entrar
                </button>
                <button
                    wire:click="switchToRegister"
                    class="flex-1 py-2.5 rounded-lg text-sm font-extrabold transition-all
                        {{ $mode === 'register'
                            ? 'bg-white text-brown-dark shadow-sm'
                            : 'text-brown-lighter hover:text-brown-light' }}"
                >
                    ✨ Criar Conta
                </button>
            </div>

            {{-- ===== FORMULÁRIO DE LOGIN ===== --}}
            @if($mode === 'login')
                <form wire:submit="login" class="space-y-5">
                    <div>
                        <label class="block text-sm font-extrabold text-brown-dark mb-2">Email</label>
                        <input
                            type="email"
                            wire:model="loginEmail"
                            class="input-warm"
                            placeholder="seu@email.com"
                            required
                            autofocus
                        >
                        @error('loginEmail')
                            <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-extrabold text-brown-dark mb-2">Senha</label>
                        <input
                            type="password"
                            wire:model="loginPassword"
                            class="input-warm"
                            placeholder="Sua senha"
                            required
                        >
                        @error('loginPassword')
                            <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            wire:model="loginRemember"
                            id="remember"
                            class="w-4 h-4 rounded accent-terracotta"
                        >
                        <label for="remember" class="text-sm text-brown-light cursor-pointer">
                            Lembrar de mim
                        </label>
                    </div>

                    <button type="submit" class="btn-terracotta w-full text-lg py-4">
                        <span wire:loading.remove wire:target="login">Entrar</span>
                        <span wire:loading wire:target="login" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Entrando...
                        </span>
                    </button>
                </form>
            @endif

            {{-- ===== FORMULÁRIO DE REGISTRO ===== --}}
            @if($mode === 'register')
                <form wire:submit="register" class="space-y-4">
                    <div>
                        <label class="block text-sm font-extrabold text-brown-dark mb-2">Nome Completo</label>
                        <input
                            type="text"
                            wire:model="registerName"
                            class="input-warm"
                            placeholder="Seu nome"
                            required
                            autofocus
                        >
                        @error('registerName')
                            <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-extrabold text-brown-dark mb-2">Email</label>
                        <input
                            type="email"
                            wire:model="registerEmail"
                            class="input-warm"
                            placeholder="seu@email.com"
                            required
                        >
                        @error('registerEmail')
                            <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-extrabold text-brown-dark mb-2">Telefone (WhatsApp)</label>
                        <input
                            type="tel"
                            wire:model="registerPhone"
                            class="input-warm"
                            placeholder="(11) 99999-9999"
                        >
                        @error('registerPhone')
                            <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-extrabold text-brown-dark mb-2">Senha</label>
                        <input
                            type="password"
                            wire:model="registerPassword"
                            class="input-warm"
                            placeholder="Mínimo 6 caracteres"
                            required
                        >
                        @error('registerPassword')
                            <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-extrabold text-brown-dark mb-2">Confirmar Senha</label>
                        <input
                            type="password"
                            wire:model="registerPasswordConfirmation"
                            class="input-warm"
                            placeholder="Repita a senha"
                            required
                        >
                        @error('registerPasswordConfirmation')
                            <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn-terracotta w-full text-lg py-4">
                        <span wire:loading.remove wire:target="register">✨ Criar Minha Conta</span>
                        <span wire:loading wire:target="register" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Criando...
                        </span>
                    </button>
                </form>
            @endif

            {{-- Footer --}}
            <div class="mt-6 pt-4 border-t border-cream-darker text-center">
                @if($mode === 'login')
                    <p class="text-sm text-brown-light">
                        Não tem conta?
                        <button wire:click="switchToRegister" class="font-extrabold text-terracotta hover:text-terracotta-dark transition-colors ml-1">
                            Criar Conta
                        </button>
                    </p>
                @else
                    <p class="text-sm text-brown-light">
                        Já tem conta?
                        <button wire:click="switchToLogin" class="font-extrabold text-terracotta hover:text-terracotta-dark transition-colors ml-1">
                            Fazer Login
                        </button>
                    </p>
                @endif
            </div>

            {{-- Dica --}}
            <div class="mt-3 text-center">
                <p class="text-xs text-brown-lighter">
                    Ambiente de testes — dados do seed disponíveis
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
</div>
