<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthForm extends Component
{
    // Modo: 'login' ou 'register'
    public string $mode = 'login';

    // ─── Login ────────────────────────────────────────────
    public string $loginEmail = '';
    public string $loginPassword = '';
    public bool $loginRemember = false;

    // ─── Registro ─────────────────────────────────────────
    public string $registerName = '';
    public string $registerEmail = '';
    public string $registerPassword = '';
    public string $registerPasswordConfirmation = '';
    public string $registerPhone = '';

    // ─── Feedback ─────────────────────────────────────────
    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    // ─── Regras de validação dinâmicas ────────────────────
    protected function rules(): array
    {
        if ($this->mode === 'register') {
            return [
                'registerName'     => ['required', 'string', 'max:255'],
                'registerEmail'    => ['required', 'email', 'max:255', 'unique:users,email'],
                'registerPassword' => ['required', 'string', 'min:6', 'same:registerPasswordConfirmation'],
                'registerPasswordConfirmation' => ['required', 'string'],
                'registerPhone'    => ['nullable', 'string', 'max:50'],
            ];
        }

        return [
            'loginEmail'    => ['required', 'email'],
            'loginPassword' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'registerEmail.unique'           => 'Este e-mail já está cadastrado.',
            'registerPassword.min'           => 'A senha deve ter no mínimo 6 caracteres.',
            'registerPassword.same'          => 'A confirmação não confere com a nova senha.',
            'loginEmail.required'            => 'Informe seu e-mail.',
            'loginPassword.required'         => 'Informe sua senha.',
        ];
    }

    public function mount(): void
    {
        // Se já estiver logado, redireciona
        if (Auth::check()) {
            $this->redirectBasedOnRole();
        }
    }

    // ─── Alternar Modo ────────────────────────────────────

    public function switchToRegister(): void
    {
        $this->mode = 'register';
        $this->resetValidation();
        $this->reset(['errorMessage', 'successMessage']);
    }

    public function switchToLogin(): void
    {
        $this->mode = 'login';
        $this->resetValidation();
        $this->reset(['errorMessage', 'successMessage']);
    }

    // ─── Login ─────────────────────────────────────────────

    public function login(): void
    {
        $this->reset(['errorMessage', 'successMessage']);

        $this->validate([
            'loginEmail'    => ['required', 'email'],
            'loginPassword' => ['required', 'string'],
        ]);

        if (Auth::attempt(
            ['email' => $this->loginEmail, 'password' => $this->loginPassword],
            $this->loginRemember
        )) {
            request()->session()->regenerate();
            $this->redirectBasedOnRole();
        } else {
            $this->errorMessage = 'E-mail ou senha incorretos.';
        }
    }

    // ─── Registro ─────────────────────────────────────────

    public function register(): void
    {
        $this->reset(['errorMessage', 'successMessage']);

        $validated = $this->validate([
            'registerName'                  => ['required', 'string', 'max:255'],
            'registerEmail'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'registerPassword'              => ['required', 'string', 'min:6', 'same:registerPasswordConfirmation'],
            'registerPasswordConfirmation'  => ['required', 'string'],
            'registerPhone'                 => ['nullable', 'string', 'max:50'],
        ]);

        $user = User::create([
            'name'     => $validated['registerName'],
            'email'    => $validated['registerEmail'],
            'phone'    => $validated['registerPhone'] ?: null,
            'password' => $validated['registerPassword'],
        ]);

        Auth::login($user);
        request()->session()->regenerate();

        $this->successMessage = 'Conta criada com sucesso! Bem-vinda(o) ao Sabor de Mãe 💚';

        // Redireciona após um breve momento para o usuário ver a mensagem
        $this->redirect('/');
    }

    // ─── Helpers ──────────────────────────────────────────

    private function redirectBasedOnRole(): void
    {
        if (Auth::user()->isAdmin()) {
            $this->redirect('/admin');
        } else {
            $this->redirect('/');
        }
    }

    public function render()
    {
        return view('livewire.auth-form')
            ->layout('layouts.auth');
    }
}
