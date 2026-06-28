<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'image_path',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class)
            ->withPivot('day_of_week')
            ->withTimestamps();
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Retorna o preço para um determinado tamanho.
     */
    public function getPriceForSize(?string $size): float
    {
        $size = $size ?: 'M';
        $productPrice = $this->prices->firstWhere('size', $size);

        if ($productPrice) {
            return (float) $productPrice->price;
        }

        // Fallback: retorna o primeiro preço disponível
        $firstPrice = $this->prices->first();
        return $firstPrice ? (float) $firstPrice->price : 0;
    }

    /**
     * Verifica se o produto tem variação de tamanhos (P e G).
     */
    public function hasSizes(): bool
    {
        $sizes = $this->prices->pluck('size')->toArray();
        return in_array('P', $sizes) && in_array('G', $sizes);
    }

    /**
     * Retorna os tamanhos disponíveis para este produto no menu atual,
     * considerando os limites de estoque.
     * Retorna um array de ProductPrice filtrado.
     */
    public function getAvailableSizes(?Menu $menu): array
    {
        if (!$menu) {
            return $this->prices->all();
        }

        return $this->prices->filter(function (ProductPrice $price) use ($menu) {
            if ($price->stock_limit === null) {
                return true;
            }

            $soldCount = OrderItem::where('product_id', $this->id)
                ->where('size', $price->size)
                ->whereHas('order', function ($query) use ($menu) {
                    $query->where('menu_id', $menu->id)
                        ->where('payment_status', 'confirmado');
                })
                ->sum('quantity');

            return $soldCount < $price->stock_limit;
        })->values()->all();
    }

    public function isPacoteSemanal(): bool
    {
        return $this->type === 'pacote_semanal';
    }

    public function isExtra(): bool
    {
        return $this->type === 'extra';
    }

    public function isSalada(): bool
    {
        return $this->type === 'salada';
    }

    public function isRefeicao(): bool
    {
        return $this->type === 'refeicao';
    }

    /**
     * Scope para produtos disponíveis.
     */
    public function scopeDisponivel($query)
    {
        return $query->where('is_available', true);
    }
}
