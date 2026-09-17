<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BakongService
{
    private string $baseUrl;
    private string $accountUsername;
    private string $accountName;
    private string $accessToken;
    private string $merchantCity;
    private int $dailyCheckLimit;

    public function __construct()
    {
        $this->baseUrl         = rtrim((string) config('services.bakong.base_url', ''), '/');
        $this->accountUsername = (string) config('services.bakong.account_username', '');
        $this->accountName     = (string) config('services.bakong.account_name', 'CEC Electronic');
        $this->accessToken     = (string) config('services.bakong.access_token', '');
        $this->merchantCity    = (string) config('services.bakong.merchant_city', 'Phnom Penh');
        $this->dailyCheckLimit = (int) config('services.bakong.daily_check_limit', 90);
    }

    public function isConfigured(): bool
    {
        return $this->accountUsername !== '';
    }

    private function http(): PendingRequest
    {
        // DNS to Bakong's API intermittently fails to resolve inside this
        // network — retry a couple of times before giving up on a single check.
        // throw: false keeps a plain non-2xx response (e.g. "not found") as a
        // normal response object instead of turning it into an exception —
        // only connection-level failures (DNS, timeout) should be retried/thrown.
        return Http::timeout(15)->retry(3, 500, throw: false)->acceptJson()->withToken($this->accessToken);
    }

    /**
     * Generate a fixed-amount KHQR string for an order — computed locally
     * following the NBC KHQR SDK spec, no API call needed.
     * Returns ['qr' => string, 'md5' => string] or null if not configured.
     */
    public function generateQrForOrder(Order $order): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return KhqrGenerator::individual(
            accountId:      $this->accountUsername,
            merchantName:   $this->accountName,
            merchantCity:   $this->merchantCity,
            amount:         (float) $order->grand_total,
            currency:       'USD',
            billNumber:     $order->order_number,
            expirationDays: 1,
        );
    }

    /**
     * Verify a payment against the official Bakong Open API using the MD5
     * hash of the KHQR string. Returns the transaction data once paid, or
     * null while unpaid / not yet found.
     *
     * Bakong caps this endpoint at a small number of requests per day for
     * the whole account. A shared daily counter guards every caller (live
     * customer polling and the background job alike) so the app can never
     * exceed that cap and get every pending order stuck until it resets.
     */
    /**
     * $enforceBudget can be set false for a deliberate, human-initiated check
     * (e.g. an admin clicking "verify now" on one order) — those are
     * naturally rate-limited by a person clicking a button, unlike automated
     * polling, so they're allowed past the shared daily cap that protects
     * against runaway automated usage. The check still counts toward the
     * shared counter so automated callers see accurate usage.
     */
    public function checkTransactionByMd5(string $md5, bool $enforceBudget = true): ?array
    {
        if ($this->baseUrl === '' || $this->accessToken === '') {
            return null;
        }

        if ($enforceBudget && $this->dailyBudgetExceeded()) {
            return null;
        }

        $this->recordDailyCheck();

        try {
            $response = $this->http()->post("{$this->baseUrl}/check_transaction_by_md5", [
                'md5' => $md5,
            ]);
        } catch (ConnectionException $e) {
            // Network/DNS hiccup reaching Bakong — treat as "not confirmed yet"
            // rather than blowing up the request; the next poll/job run retries.
            Log::warning('Bakong check_transaction_by_md5 connection failed', [
                'md5' => $md5,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful() || $response->json('responseCode') !== 0) {
            return null;
        }

        return $response->json('data');
    }

    private function dailyBudgetExceeded(): bool
    {
        if ($this->dailyCheckLimit <= 0) {
            return false;
        }

        return (int) Cache::get($this->dailyBudgetKey(), 0) >= $this->dailyCheckLimit;
    }

    private function recordDailyCheck(): void
    {
        $key = $this->dailyBudgetKey();

        Cache::add($key, 0, now()->endOfDay()->addSecond());
        Cache::increment($key);
    }

    private function dailyBudgetKey(): string
    {
        return 'bakong:daily-checks:' . now()->toDateString();
    }
}
