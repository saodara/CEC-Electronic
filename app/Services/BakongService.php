<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BakongService
{
    private string $baseUrl;
    private string $accountId;
    private string $token;
    private string $merchantName;
    private string $merchantCity;

    public function __construct()
    {
        $this->baseUrl      = rtrim((string) config('services.bakong.relay_url', ''), '/');
        $this->accountId    = (string) config('services.bakong.account_id', '');
        $this->token        = (string) config('services.bakong.token', '');
        $this->merchantName = (string) config('services.bakong.merchant_name', 'CEC Electronic');
        $this->merchantCity = (string) config('services.bakong.merchant_city', 'Phnom Penh');
    }

    public function isConfigured(): bool
    {
        return $this->accountId !== '';
    }

    private function http(): PendingRequest
    {
        $client = Http::timeout(15)->acceptJson();

        if ($this->token !== '') {
            $client = $client->withToken($this->token);
        }

        return $client;
    }

    /**
     * Generate a fixed-amount KHQR string for an order using the local KHQR generator.
     * No relay API call needed — format follows the official NBC KHQR SDK spec.
     * Returns ['qr' => string, 'md5' => string] or null if account ID is not set.
     */
    public function generateQrForOrder(Order $order): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return KhqrGenerator::individual(
            accountId:      $this->accountId,
            merchantName:   $this->merchantName,
            merchantCity:   $this->merchantCity,
            amount:         (float) $order->grand_total,
            currency:       'USD',
            billNumber:     $order->order_number,
            expirationDays: 1,
        );
    }

    /**
     * Convert a raw KHQR string into a Base64 PNG image (data:image/png;base64,…).
     */
    public function generateImage(string $qr): ?string
    {
        if (! $this->baseUrl) {
            return null;
        }

        $response = $this->http()->post("{$this->baseUrl}/v1/generate_khqr_image", [
            'qr' => $qr,
        ]);

        if (! $response->successful() || $response->json('responseCode') !== 0) {
            return null;
        }

        return $response->json('data.image');
    }

    /**
     * Create a hosted web checkout session with a fixed amount.
     * Returns the session data array (session_id, checkout_url) or null on failure.
     */
    public function createWebCheckout(Order $order, string $returnUrl, string $webhookUrl): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $response = $this->http()->post("{$this->baseUrl}/v1/web_checkouts/create", [
            'trans_id'   => $order->order_number,
            'req_custom' => [
                'lang' => 'km',
                'ttl'  => 60,
            ],
            'req_khqr' => [
                'account_id'    => $this->accountId,
                'merchant_name' => $this->merchantName,
                'merchant_city' => $this->merchantCity,
                'amount'        => (float) $order->grand_total,
                'currency'      => 'USD',
            ],
            'req_url' => [
                'return_url'  => $returnUrl,
                'webhook_url' => $webhookUrl,
            ],
        ]);

        if (! $response->successful() || $response->json('responseCode') !== 0) {
            Log::warning('Bakong web checkout creation failed', [
                'order'    => $order->order_number,
                'response' => $response->json(),
            ]);
            return null;
        }

        return $response->json('data');
    }

    /**
     * Fetch the current status of a web checkout session.
     */
    public function getCheckoutDetails(string $sessionId): ?array
    {
        if (! $this->baseUrl) {
            return null;
        }

        $response = $this->http()->post("{$this->baseUrl}/v1/web_checkouts/details", [
            'session_id' => $sessionId,
        ]);

        if (! $response->successful() || $response->json('responseCode') !== 0) {
            return null;
        }

        return $response->json('data');
    }

    /**
     * Check a transaction by MD5 hash (from QR generation).
     */
    public function checkTransactionByMd5(string $md5): ?array
    {
        if (! $this->baseUrl) {
            return null;
        }

        $response = $this->http()->post("{$this->baseUrl}/v1/check_transaction_by_md5", [
            'md5' => $md5,
        ]);

        if (! $response->successful() || $response->json('responseCode') !== 0) {
            return null;
        }

        return $response->json('data');
    }
}
