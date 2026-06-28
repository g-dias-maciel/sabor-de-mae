<?php

namespace App\Livewire\Admin;

use App\Models\Menu;
use App\Models\Product;
use Livewire\Component;

class MenuManager extends Component
{
    /** @var Menu|null */
    public $editingMenu = null;

    // Criação / Edição de menu
    public $menuStartDate;
    public $menuStatus = 'planejamento';

    // Produtos vinculados por dia (dropdowns)
    public $dayMeals = [];       // [day => product_id|null] refeição do dia
    public $daySalads = [];      // [day => product_id|null] salada do dia
    public $weeklyPackageId = null; // ID do pacote semanal
    public $extraIds = [];       // IDs dos extras selecionados

    // Controle de UI
    public $showForm = false;
    public $showDeleteConfirm = false;
    public $deleteMenuId = null;

    // Mensagem flash
    public $message = null;
    public $messageType = 'success';

    protected $rules = [
        'menuStartDate' => 'required|date|after_or_equal:today',
        'menuStatus'    => 'required|in:planejamento,aberto,encerrado',
    ];

    protected $listeners = ['refreshMenuManager' => '$refresh'];

    public function render()
    {
        $menus = Menu::withCount(['orders', 'products'])
            ->orderBy('start_date', 'desc')
            ->get();

        $allProducts = Product::where('is_available', true)
            ->with('prices')
            ->get();

        // Produtos filtrados por tipo para os dropdowns
        $refeicoes = $allProducts->filter(fn($p) => $p->isRefeicao());
        $saladas   = $allProducts->filter(fn($p) => $p->isSalada());
        $pacotes   = $allProducts->filter(fn($p) => $p->isPacoteSemanal());
        $extras    = $allProducts->filter(fn($p) => $p->isExtra());

        // Produtos indexados por ID para lookup rápido na view
        $productsById = $allProducts->keyBy('id');

        $dayLabels = [
            1 => '🥩 Segunda-feira',
            2 => '🥞 Terça-feira',
            3 => '🍲 Quarta-feira',
            4 => '🧆 Quinta-feira',
            5 => '🍗 Sexta-feira',
            6 => '🍽️ Sábado',
            7 => '🍲 Domingo',
        ];

        return view('livewire.admin.menu-manager', compact(
            'menus', 'refeicoes', 'saladas', 'pacotes', 'extras',
            'productsById', 'dayLabels'
        ))->layout('layouts.admin');
    }

    // ─── Helpers ───────────────────────────────────────────

    private function flash(string $msg, string $type = 'success'): void
    {
        $this->message = $msg;
        $this->messageType = $type;
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingMenu = null;
        $this->reset(['menuStartDate', 'menuStatus', 'dayMeals', 'daySalads', 'weeklyPackageId', 'extraIds']);
        $this->resetErrorBag();
    }

    // ─── Criar / Editar Menu ───────────────────────────────

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->menuStartDate = now()->addWeek()->startOfWeek()->format('Y-m-d');
        $this->menuStatus = 'planejamento';
        // Inicializa arrays vazios para os 7 dias
        $this->dayMeals = array_fill(1, 7, null);
        $this->daySalads = array_fill(1, 7, null);
        $this->weeklyPackageId = null;
        $this->extraIds = [];
        $this->showForm = true;
    }

    public function openEditForm(int $menuId): void
    {
        $this->resetForm();
        $this->editingMenu = Menu::with('products')->findOrFail($menuId);

        $this->menuStartDate = $this->editingMenu->start_date->format('Y-m-d');
        $this->menuStatus = $this->editingMenu->status;

        // Inicializa arrays vazios
        $this->dayMeals = array_fill(1, 7, null);
        $this->daySalads = array_fill(1, 7, null);
        $this->weeklyPackageId = null;
        $this->extraIds = [];

        // Popula a partir dos produtos vinculados
        foreach ($this->editingMenu->products as $product) {
            $day = $product->pivot->day_of_week;

            if ($product->isRefeicao()) {
                $this->dayMeals[$day ?? 0] = $product->id;
            } elseif ($product->isSalada()) {
                $this->daySalads[$day ?? 0] = $product->id;
            } elseif ($product->isPacoteSemanal()) {
                $this->weeklyPackageId = $product->id;
            } elseif ($product->isExtra()) {
                $this->extraIds[] = $product->id;
            }
        }

        $this->showForm = true;
    }

    public function saveMenu(): void
    {
        $this->validate();

        $start = \Carbon\Carbon::parse($this->menuStartDate)->startOfWeek();
        $end = $start->copy()->addDays(6);

        if ($this->editingMenu) {
            $menu = $this->editingMenu;
            $menu->update([
                'start_date' => $start,
                'end_date'   => $end,
                'status'     => $this->menuStatus,
            ]);
        } else {
            $menu = Menu::create([
                'start_date' => $start,
                'end_date'   => $end,
                'status'     => $this->menuStatus,
            ]);
        }

        // Reconstrói selectedProducts a partir dos arrays diários
        $selectedProducts = [];

        // Refeições por dia
        foreach ($this->dayMeals as $day => $productId) {
            if ($productId) {
                $selectedProducts[$productId] = (int) $day;
            }
        }

        // Saladas por dia
        foreach ($this->daySalads as $day => $productId) {
            if ($productId) {
                $selectedProducts[$productId] = (int) $day;
            }
        }

        // Pacote semanal (sem dia)
        if ($this->weeklyPackageId) {
            $selectedProducts[$this->weeklyPackageId] = true;
        }

        // Extras (sem dia)
        foreach ($this->extraIds as $productId) {
            if ($productId) {
                $selectedProducts[$productId] = true;
            }
        }

        // Sincroniza produtos e dias
        $syncData = [];
        foreach ($selectedProducts as $productId => $value) {
            if ($value === true) {
                $syncData[$productId] = ['day_of_week' => null];
            } elseif ($value && (int) $value > 0) {
                $syncData[$productId] = ['day_of_week' => (int) $value];
            }
        }
        $menu->products()->sync($syncData);

        $this->flash($this->editingMenu ? 'Cardápio atualizado com sucesso!' : 'Cardápio criado com sucesso!');
        $this->resetForm();
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    // ─── Status ────────────────────────────────────────────

    public function updateStatus(int $menuId, string $status): void
    {
        $menu = Menu::findOrFail($menuId);

        // Só permite abrir se houver pelo menos 1 produto vinculado
        if ($status === 'aberto') {
            $count = $menu->products()->count();
            if ($count === 0) {
                $this->flash('Adicione pelo menos um produto ao cardápio antes de abri-lo.', 'error');
                return;
            }

            // Fecha qualquer outro menu aberto
            Menu::where('status', 'aberto')->where('id', '!=', $menu->id)->update(['status' => 'encerrado']);
        }

        $menu->update(['status' => $status]);

        $labels = ['planejamento' => 'Planejamento', 'aberto' => 'Aberto ✅', 'encerrado' => 'Encerrado 🔒'];
        $this->flash('Cardápio marcado como "' . ($labels[$status] ?? $status) . '".');
    }

    // ─── Excluir ───────────────────────────────────────────

    public function confirmDelete(int $menuId): void
    {
        $this->showDeleteConfirm = true;
        $this->deleteMenuId = $menuId;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteMenuId = null;
    }

    public function deleteMenu(): void
    {
        $menu = Menu::findOrFail($this->deleteMenuId);

        if ($menu->orders()->count() > 0) {
            $this->flash('Não é possível excluir um cardápio que já possui pedidos.', 'error');
        } else {
            $menu->delete();
            $this->flash('Cardápio excluído.');
        }

        $this->showDeleteConfirm = false;
        $this->deleteMenuId = null;
    }

    // ─── Helpers de Extras ────────────────────────────────

    public function toggleExtra(int $productId): void
    {
        $index = array_search($productId, $this->extraIds);
        if ($index !== false) {
            unset($this->extraIds[$index]);
            $this->extraIds = array_values($this->extraIds);
        } else {
            $this->extraIds[] = $productId;
        }
    }
}
