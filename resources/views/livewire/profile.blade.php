<div class="container mx-auto px-4 py-8 md:py-12 max-w-2xl fade-in" x-data="{ showMsg: @json($message ? true : false) }">
    {{-- Cabeçalho --}}
    <div class="text-center mb-8">
        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-terracotta to-terracotta-dark mx-auto mb-4 flex items-center justify-center shadow-lg">
            <span class="text-2xl">👤</span>
        </div>
        <h1 class="title-hand text-3xl md:text-4xl text-brown-dark">Meu Perfil</h1>
        <p class="text-brown-light text-sm mt-1">Gerencie seus dados e endereços</p>
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

    {{-- ===== DADOS PESSOAIS ===== --}}
    <div class="card-artisan p-6 mb-6">
        <h2 class="font-extrabold text-brown-dark text-lg mb-5 flex items-center gap-2">
            <span class="w-8 h-8 rounded-full bg-terracotta-lighter flex items-center justify-center text-sm">📝</span>
            Dados Pessoais
        </h2>

        <form wire:submit="updateProfile" class="space-y-4">
            <div>
                <label class="block text-sm font-extrabold text-brown-dark mb-2">Nome</label>
                <input type="text" wire:model="name" class="input-warm w-full" placeholder="Seu nome">
                @error('name') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-extrabold text-brown-dark mb-2">Email</label>
                <input type="email" wire:model="email" class="input-warm w-full" placeholder="seu@email.com">
                @error('email') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-extrabold text-brown-dark mb-2">Telefone</label>
                <input type="text" wire:model="phone" class="input-warm w-full" placeholder="(11) 99999-9999">
                @error('phone') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-terracotta text-sm">
                    💾 Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    {{-- ===== ALTERAR SENHA ===== --}}
    <div class="card-artisan p-6 mb-6">
        <h2 class="font-extrabold text-brown-dark text-lg mb-5 flex items-center gap-2">
            <span class="w-8 h-8 rounded-full bg-olive-lighter flex items-center justify-center text-sm">🔐</span>
            Alterar Senha
        </h2>

        <form wire:submit="updatePassword" class="space-y-4">
            <div>
                <label class="block text-sm font-extrabold text-brown-dark mb-2">Senha Atual</label>
                <input type="password" wire:model="currentPassword" class="input-warm w-full" placeholder="••••••••">
                @error('currentPassword') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-extrabold text-brown-dark mb-2">Nova Senha</label>
                    <input type="password" wire:model="newPassword" class="input-warm w-full" placeholder="Mínimo 6 caracteres">
                    @error('newPassword') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-extrabold text-brown-dark mb-2">Confirmar Nova Senha</label>
                    <input type="password" wire:model="newPasswordConfirmation" class="input-warm w-full" placeholder="Repita a senha">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-olive text-sm">
                    🔄 Alterar Senha
                </button>
            </div>
        </form>
    </div>

    {{-- ===== ENDEREÇOS ===== --}}
    <div class="card-artisan p-6">
        <div class="flex justify-between items-center mb-5">
            <h2 class="font-extrabold text-brown-dark text-lg flex items-center gap-2">
                <span class="w-8 h-8 rounded-full bg-olive-lighter flex items-center justify-center text-sm">📍</span>
                Meus Endereços
            </h2>
            @if(!$showAddressForm)
                <button wire:click="openNewAddress" class="btn-outline text-xs py-1.5 px-3">
                    + Novo
                </button>
            @endif
        </div>

        {{-- Formulário de endereço --}}
        @if($showAddressForm)
            <div class="bg-cream rounded-xl p-5 mb-4 border border-cream-darker">
                <h3 class="font-bold text-brown-dark text-sm mb-4">
                    {{ $editingAddressId ? '✏️ Editar Endereço' : '🆕 Novo Endereço' }}
                </h3>

                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-brown-dark mb-1">Rua</label>
                            <input type="text" wire:model="addressStreet" class="input-warm w-full" placeholder="Rua...">
                            @error('addressStreet') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-brown-dark mb-1">Número</label>
                            <input type="text" wire:model="addressNumber" class="input-warm w-full" placeholder="123">
                            @error('addressNumber') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-brown-dark mb-1">Complemento</label>
                        <input type="text" wire:model="addressComplement" class="input-warm w-full" placeholder="Apto, bloco, etc.">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-brown-dark mb-1">Bairro</label>
                            <input type="text" wire:model="addressNeighborhood" class="input-warm w-full" placeholder="Bairro">
                            @error('addressNeighborhood') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-brown-dark mb-1">CEP</label>
                            <input type="text" wire:model="addressZipCode" class="input-warm w-full" placeholder="00000-000">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-brown-dark mb-1">Cidade</label>
                        <input type="text" wire:model="addressCity" class="input-warm w-full" placeholder="Cidade">
                        @error('addressCity') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-4 pt-3 border-t border-cream-darker">
                    <button wire:click="cancelAddress" class="btn-ghost text-xs">Cancelar</button>
                    <button wire:click="saveAddress" class="btn-terracotta text-xs">
                        💾 {{ $editingAddressId ? 'Atualizar' : 'Adicionar' }}
                    </button>
                </div>
            </div>
        @endif

        {{-- Lista de endereços --}}
        @forelse($addresses as $address)
            <div class="border {{ $address->is_default ? 'border-terracotta bg-terracotta-lighter/20' : 'border-cream-darker' }} rounded-xl p-4 mb-3 transition-all">
                <div class="flex justify-between items-start gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            @if($address->is_default)
                                <span class="text-xs bg-terracotta text-white px-2 py-0.5 rounded-full font-bold">⭐ Padrão</span>
                            @endif
                            <p class="font-bold text-brown-dark text-sm">
                                {{ $address->street }}, {{ $address->number }}
                                @if($address->complement)
                                    <span class="text-brown-light font-normal">({{ $address->complement }})</span>
                                @endif
                            </p>
                        </div>
                        <p class="text-xs text-brown-light">
                            {{ $address->neighborhood }} — {{ $address->city }}
                            @if($address->zip_code) • CEP {{ $address->zip_code }} @endif
                        </p>
                    </div>

                    <div class="flex gap-1 shrink-0">
                        @if(!$address->is_default)
                            <button wire:click="setDefaultAddress({{ $address->id }})" class="btn-ghost text-xs py-1 px-2" title="Tornar padrão">
                                ⭐
                            </button>
                        @endif
                        <button wire:click="editAddress({{ $address->id }})" class="btn-ghost text-xs py-1 px-2" title="Editar">
                            ✏️
                        </button>
                        <button wire:click="deleteAddress({{ $address->id }})" class="btn-ghost text-xs py-1 px-2 text-red-500 hover:text-red-700" title="Excluir">
                            🗑️
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-6 text-brown-lighter">
                <p class="text-3xl mb-2">📍</p>
                <p class="text-sm font-bold">Nenhum endereço cadastrado</p>
                <p class="text-xs">Adicione um endereço para agilizar seus pedidos</p>
            </div>
        @endforelse
    </div>
</div>
