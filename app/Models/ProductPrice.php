<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size',
        'price',
        'stock_limit',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_limit' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Label legível para exibição.
     */
    public function label(): string
    {
        return match ($this->size) {
            'P' => 'Pequena (500g)',
            'M' => 'Média',
            'G' => 'Grande (750g)',
            default => $this->size,
        };
    }

    /**
     * Label curto para carrinho/checkout.
     */
    public function shortLabel(): string
    {
        return match ($this->size) {
            'P' => 'Pequena (500g)',
            'M' => 'Único',
            'G' => 'Grande (750g)',
            default => $this->size,
        };
    }
}
