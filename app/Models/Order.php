<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'menu_id',
        'total',
        'payment_method',
        'payment_status',
        'delivery_status',
        'delivery_address',
        'delivery_zone_id',
        'gateway_transaction_id',
        'pix_qr_code',
        'pix_copy_paste',
        'pix_qr_code_base64',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    /**
     * Recalcula o total do pedido com base nos itens e taxa de entrega.
     */
    public function recalcularTotal(): void
    {
        $subtotal = $this->items->sum(function ($item) {
            return $item->price_at_purchase * $item->quantity;
        });

        $taxaEntrega = 0;
        if ($this->deliveryZone) {
            $taxaEntrega = $this->deliveryZone->fee;
        }

        $this->update(['total' => $subtotal + $taxaEntrega]);
    }
}
