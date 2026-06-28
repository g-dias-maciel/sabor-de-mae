<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * @throws \Exception
     */
    public function checkout(
        User $user,
        Menu $menu,
        array $items,
        ?int $deliveryZoneId = null,
        string $deliveryAddress = '',
        string $paymentMethod = 'pix'
    ): Order {
        if (!$menu->aceitaPedidos()) {
            if (now()->isSunday()) {
                throw new \Exception('Pedidos encerrados para esta semana. O cardápio da próxima semana estará disponível em breve.');
            }
            throw new \Exception('Pedidos não são aceitos neste momento. O prazo de pedidos encerra sábado às 23:59.');
        }

        if (empty($items)) {
            throw new \Exception('O carrinho está vazio.');
        }

        return DB::transaction(function () use ($user, $menu, $items, $deliveryZoneId, $deliveryAddress, $paymentMethod) {
            $order = Order::create([
                'user_id' => $user->id,
                'menu_id' => $menu->id,
                'total' => 0,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pendente',
                'delivery_status' => 'pendente',
                'delivery_address' => $deliveryAddress,
                'delivery_zone_id' => $deliveryZoneId,
            ]);

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $size = $item['size'] ?? 'M';

                // Usa o preço correto conforme o tamanho
                $price = $product->getPriceForSize($size);

                // Notas: inclui salada se selecionada
                $notes = $item['notes'] ?? null;
                if (!empty($item['salad_name'])) {
                    $notes = trim(($notes ? $notes . ' | ' : '') . '🥗 + ' . $item['salad_name']);
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'size' => $size,
                    'price_at_purchase' => $price,
                    'notes' => $notes,
                ]);
            }

            $order->refresh();
            $order->recalcularTotal();

            return $order;
        });
    }
}
