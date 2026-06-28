<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    /**
     * Recebe notificações de pagamento do Mercado Pago.
     * Sempre retorna 200 para evitar reenvios do gateway.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $action = $request->input('action');

        // Só processa notificações de pagamento
        if ($action !== 'payment.updated' && $action !== 'payment.created') {
            return response()->json(['status' => 'ignored']);
        }

        $transactionId = $request->input('data.id');

        if (!$transactionId) {
            return response()->json(['status' => 'missing id']);
        }

        $order = Order::where('gateway_transaction_id', (string) $transactionId)->first();

        if (!$order) {
            Log::info('MercadoPago: pedido não encontrado para transação', [
                'transaction_id' => $transactionId,
            ]);

            return response()->json(['status' => 'not found']);
        }

        // Só atualiza se ainda estiver pendente
        if ($order->payment_status === 'pendente') {
            $order->update(['payment_status' => 'pago']);

            Log::info('MercadoPago: pagamento confirmado via webhook', [
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
