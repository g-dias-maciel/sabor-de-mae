<?php

namespace App\Livewire;

use App\Models\Address;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Profile extends Component
{
    // ─── Dados Pessoais ───────────────────────────────────
    public $name;
    public $email;
    public $phone;

    // ─── Senha ────────────────────────────────────────────
    public $currentPassword = '';
    public $newPassword = '';
    public $newPasswordConfirmation = '';

    // ─── Endereços ────────────────────────────────────────
    public $editingAddressId = null;
    public $addressStreet;
    public $addressNumber;
    public $addressComplement;
    public $addressNeighborhood;
    public $addressCity = 'São Paulo';
    public $addressZipCode;

    // ─── Estados de UI ────────────────────────────────────
    public $message = null;
    public $messageType = 'success';
    public $showAddressForm = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
    }

    public function render()
    {
        return view('livewire.profile', [
            'addresses' => Auth::user()->addresses()->orderBy('is_default', 'desc')->get(),
        ])->layout('layouts.app');
    }

    // ─── Dados Pessoais ───────────────────────────────────

    public function updateProfile(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($validated);

        $this->flash('Perfil atualizado com sucesso! 💚');
    }

    // ─── Senha ────────────────────────────────────────────

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'currentPassword'          => ['required'],
            'newPassword'              => ['required', 'string', 'min:6', 'same:newPasswordConfirmation'],
            'newPasswordConfirmation'  => ['required'],
        ], [
            'newPassword.min'          => 'A senha deve ter pelo menos 6 caracteres.',
            'newPassword.same'         => 'A confirmação não confere com a nova senha.',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Senha atual incorreta.');
            return;
        }

        $user->update(['password' => $validated['newPassword']]);

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->flash('Senha alterada com sucesso! 🔐');
    }

    // ─── Endereços ────────────────────────────────────────

    public function openNewAddress(): void
    {
        $this->resetAddressForm();
        $this->showAddressForm = true;
    }

    public function editAddress(int $addressId): void
    {
        $address = Auth::user()->addresses()->findOrFail($addressId);
        $this->editingAddressId = $address->id;
        $this->addressStreet = $address->street;
        $this->addressNumber = $address->number;
        $this->addressComplement = $address->complement;
        $this->addressNeighborhood = $address->neighborhood;
        $this->addressCity = $address->city;
        $this->addressZipCode = $address->zip_code;
        $this->showAddressForm = true;
    }

    public function saveAddress(): void
    {
        $validated = $this->validate([
            'addressStreet'      => ['required', 'string', 'max:255'],
            'addressNumber'      => ['required', 'string', 'max:20'],
            'addressComplement'  => ['nullable', 'string', 'max:255'],
            'addressNeighborhood' => ['required', 'string', 'max:255'],
            'addressCity'        => ['required', 'string', 'max:255'],
            'addressZipCode'     => ['nullable', 'string', 'max:20'],
        ]);

        $data = [
            'street'      => $validated['addressStreet'],
            'number'      => $validated['addressNumber'],
            'complement'  => $validated['addressComplement'],
            'neighborhood' => $validated['addressNeighborhood'],
            'city'        => $validated['addressCity'],
            'zip_code'    => $validated['addressZipCode'],
        ];

        if ($this->editingAddressId) {
            Auth::user()->addresses()->where('id', $this->editingAddressId)->update($data);
            $this->flash('Endereço atualizado! 📍');
        } else {
            $address = Auth::user()->addresses()->create($data);
            // Se for o primeiro endereço, marca como padrão
            if (Auth::user()->addresses()->count() === 1) {
                $address->setAsDefault();
            }
            $this->flash('Endereço adicionado! 📍');
        }

        $this->resetAddressForm();
        $this->showAddressForm = false;
    }

    public function cancelAddress(): void
    {
        $this->resetAddressForm();
        $this->showAddressForm = false;
    }

    public function setDefaultAddress(int $addressId): void
    {
        $address = Auth::user()->addresses()->findOrFail($addressId);
        $address->setAsDefault();
        $this->flash('Endereço padrão atualizado! ⭐');
    }

    public function deleteAddress(int $addressId): void
    {
        $address = Auth::user()->addresses()->findOrFail($addressId);

        if ($address->is_default && Auth::user()->addresses()->count() > 1) {
            $this->flash('Defina outro endereço como padrão antes de excluir este.', 'error');
            return;
        }

        $address->delete();
        $this->flash('Endereço removido.');
    }

    // ─── Helpers ──────────────────────────────────────────

    private function flash(string $msg, string $type = 'success'): void
    {
        $this->message = $msg;
        $this->messageType = $type;
    }

    private function resetAddressForm(): void
    {
        $this->editingAddressId = null;
        $this->reset([
            'addressStreet', 'addressNumber', 'addressComplement',
            'addressNeighborhood', 'addressCity', 'addressZipCode',
        ]);
        $this->resetErrorBag();
    }
}
