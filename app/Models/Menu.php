<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('day_of_week')
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope para encontrar o menu ativo da semana vigente.
     * O menu está ativo se o status for 'aberto' e a data atual estiver
     * dentro do período do menu.
     */
    public function scopeAtivo($query)
    {
        $agora = now();

        // Domingo: mostra o cardápio da PRÓXIMA semana (pré-venda)
        if ($agora->isSunday()) {
            $proximaSegunda = $agora->copy()->addDay()->startOfWeek()->toDateString();
            return $query->where('status', 'aberto')
                ->whereDate('start_date', $proximaSegunda);
        }

        // Segunda a sábado: cardápio desta semana
        $hoje = $agora->toDateString();
        return $query->where('status', 'aberto')
            ->whereDate('start_date', '<=', $hoje)
            ->whereDate('end_date', '>=', $hoje);
    }

    /**
     * Scope para encontrar o cardápio da PRÓXIMA semana (pré-venda).
     * Clientes sempre veem o cardápio da semana seguinte, para fazer
     * pedidos com antecedência.
     *
     * Exemplo: quarta-feira 01/07 → mostra o menu que inicia segunda 06/07.
     */
    public function scopeProximaSemana($query)
    {
        $proximaSegunda = now()->copy()->startOfWeek()->addWeek()->toDateString();

        return $query->where('status', 'aberto')
            ->whereDate('start_date', $proximaSegunda);
    }

    /**
     * Verifica se o menu atual aceita novos pedidos.
     * Pedidos só são aceitos até sábado 23:59.
     */
    public function aceitaPedidos(): bool
    {
        if ($this->status !== 'aberto') {
            return false;
        }

        $agora = now();

        // Pré-venda: cardápio da próxima semana (ex: domingo) — sempre aceita
        if ($this->start_date->greaterThan($agora->toDateString())) {
            return true;
        }

        // Domingo: não aceita pedidos para o cardápio desta semana (entrega é hoje)
        if ($agora->isSunday()) {
            return false;
        }

        // Sábado após 23:59 não aceita pedidos para esta semana
        $sabadoFim = $this->start_date->copy()->addDays(5)->setTime(23, 59, 59);
        if ($agora->greaterThan($sabadoFim)) {
            return false;
        }

        return true;
    }

    /**
     * Encerra este menu (muda status para 'encerrado').
     */
    public function encerrar(): void
    {
        $this->update(['status' => 'encerrado']);
    }

    /**
     * Cria o próximo menu semanal automaticamente.
     */
    public static function criarProximo(): self
    {
        $ultimoMenu = self::orderBy('end_date', 'desc')->first();
        $proximaSegunda = $ultimoMenu
            ? $ultimoMenu->end_date->copy()->addDay()->startOfWeek()
            : now()->startOfWeek();

        return self::create([
            'start_date' => $proximaSegunda,
            'end_date' => $proximaSegunda->copy()->addDays(6),
            'status' => 'aberto',
        ]);
    }
}
