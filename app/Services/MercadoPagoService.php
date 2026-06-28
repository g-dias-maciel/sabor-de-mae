<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    protected string $baseUrl;
    protected string $accessToken;
    protected string $publicKey;

    public function __construct()
    {
        $this->baseUrl = config('services.mercadopago.base_url', 'https://api.mercadopago.com');
        $this->accessToken = config('services.mercadopago.access_token');
        $this->publicKey = config('services.mercadopago.public_key');
    }

    /**
     * Cria um pagamento PIX no Mercado Pago.
     *
     * @return array{success: bool, transaction_id: int|null, qr_code: string|null, qr_code_base64: string|null, message: string}
     */
    public function createPixPayment(Order $order): array
    {
        // Valida se as credenciais estão configuradas
        if (empty($this->accessToken)) {
            Log::warning('MercadoPago: access token não configurado', [
                'order_id' => $order->id,
            ]);

            return [
                'success' => false,
                'transaction_id' => null,
                'qr_code' => null,
                'qr_code_base64' => null,
                'message' => 'Pagamento PIX indisponível no momento. Configure o token do Mercado Pago no .env.',
            ];
        }

        $payload = $this->buildPixPayload($order);

        try {
            $response = Http::withToken($this->accessToken)
                ->withHeaders(['X-Idempotency-Key' => $this->generateIdempotencyKey($order->id)])
                ->post("{$this->baseUrl}/v1/payments", $payload);

            if ($response->successful()) {
                $data = $response->json();

                $transactionId = $data['id'];
                $qrCode = $data['point_of_interaction']['transaction_data']['qr_code'] ?? null;
                $qrCodeBase64 = $data['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;

                $order->update([
                    'gateway_transaction_id' => (string) $transactionId,
                    'pix_qr_code' => $qrCode,
                    'pix_copy_paste' => $qrCode,
                    'pix_qr_code_base64' => $qrCodeBase64,
                ]);

                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'qr_code' => $qrCode,
                    'qr_code_base64' => $qrCodeBase64,
                    'message' => 'Pagamento PIX gerado com sucesso.',
                ];
            }

            Log::error('MercadoPago: erro ao criar pagamento', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return [
                'success' => false,
                'transaction_id' => null,
                'qr_code' => null,
                'qr_code_base64' => null,
                'message' => 'Falha ao gerar pagamento PIX. Tente novamente.',
            ];
        } catch (\Exception $e) {
            Log::error('MercadoPago: exceção ao criar pagamento', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'transaction_id' => null,
                'qr_code' => null,
                'qr_code_base64' => null,
                'message' => 'Falha ao gerar pagamento PIX. Tente novamente.',
            ];
        }
    }

    /**
     * Monta o payload do PIX conforme a API do Mercado Pago.
     */
    protected function buildPixPayload(Order $order): array
    {
        $user = $order->user;

        return [
            'transaction_amount' => (float) $order->total,
            'description' => "Pedido Sabor de Mãe #{$order->id}",
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $user->email,
                'first_name' => $user->name,
                'identification' => [
                    'type' => 'CPF',
                    'number' => $user->cpf ?? '00000000000',
                ],
            ],
            'notification_url' => url('/webhooks/mercadopago'),
        ];
    }

    /**
     * Gera chave de idempotência única por pedido.
     */
    protected function generateIdempotencyKey(int $orderId): string
    {
        return 'sdm-order-' . $orderId . '-' . now()->timestamp;
    }
}
