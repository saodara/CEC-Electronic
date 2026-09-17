<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Services\BakongService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BakongServiceTest extends TestCase
{
    private function order(array $overrides = []): Order
    {
        return new Order(array_merge([
            'order_number' => 'EH-20260906-1234',
            'grand_total' => 49.5,
        ], $overrides));
    }

    private function configureBakong(array $overrides = []): void
    {
        config(array_merge([
            'services.bakong.base_url' => 'https://api-bakong.test/v1',
            'services.bakong.account_username' => '855012345678',
            'services.bakong.account_name' => 'CEC Electronic',
            'services.bakong.access_token' => 'test-token',
            'services.bakong.merchant_city' => 'Phnom Penh',
        ], $overrides));
    }

    public function test_is_configured_is_false_when_account_username_is_blank(): void
    {
        config(['services.bakong.account_username' => '']);

        $service = new BakongService();

        $this->assertFalse($service->isConfigured());
    }

    public function test_is_configured_is_true_when_account_username_is_set(): void
    {
        config(['services.bakong.account_username' => '855012345678']);

        $service = new BakongService();

        $this->assertTrue($service->isConfigured());
    }

    public function test_generate_qr_for_order_returns_null_when_not_configured(): void
    {
        config(['services.bakong.account_username' => '']);
        $service = new BakongService();

        $result = $service->generateQrForOrder($this->order());

        $this->assertNull($result);
    }

    public function test_generate_qr_for_order_returns_qr_and_md5_when_configured(): void
    {
        $this->configureBakong();
        $service = new BakongService();

        $order = $this->order([
            'order_number' => 'EH-20260906-9999',
            'grand_total' => 12.34,
        ]);

        $result = $service->generateQrForOrder($order);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('qr', $result);
        $this->assertArrayHasKey('md5', $result);
        $this->assertSame(md5($result['qr']), $result['md5']);
        // The order's account username, amount and order number are wired into the QR payload.
        $this->assertStringContainsString('855012345678', $result['qr']);
        $this->assertStringContainsString('12.34', $result['qr']);
        $this->assertStringContainsString('EH-20260906-9999', $result['qr']);
    }

    public function test_check_transaction_by_md5_returns_null_when_base_url_blank(): void
    {
        $this->configureBakong(['services.bakong.base_url' => '']);
        $service = new BakongService();

        $result = $service->checkTransactionByMd5('abc123');

        $this->assertNull($result);
    }

    public function test_check_transaction_by_md5_returns_null_when_access_token_blank(): void
    {
        $this->configureBakong(['services.bakong.access_token' => '']);
        $service = new BakongService();

        $result = $service->checkTransactionByMd5('abc123');

        $this->assertNull($result);
    }

    public function test_check_transaction_by_md5_returns_data_on_success(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 0,
                'responseMessage' => 'Getting transaction data successfully',
                'data' => ['hash' => 'abc123', 'amount' => 12.34],
            ]),
        ]);

        $service = new BakongService();

        $result = $service->checkTransactionByMd5('abc123');

        $this->assertSame(['hash' => 'abc123', 'amount' => 12.34], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-bakong.test/v1/check_transaction_by_md5'
                && $request['md5'] === 'abc123'
                && $request->hasHeader('Authorization', 'Bearer test-token');
        });
    }

    public function test_check_transaction_by_md5_returns_null_when_not_yet_paid(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 1,
                'responseMessage' => 'Transaction not found',
                'data' => null,
            ]),
        ]);

        $service = new BakongService();

        $result = $service->checkTransactionByMd5('abc123');

        $this->assertNull($result);
    }

    public function test_check_transaction_by_md5_returns_null_on_unsuccessful_response(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response(['error' => 'boom'], 500),
        ]);

        $service = new BakongService();

        $result = $service->checkTransactionByMd5('abc123');

        $this->assertNull($result);
    }

    public function test_check_transaction_by_md5_skips_the_call_once_daily_budget_is_used_up(): void
    {
        $this->configureBakong(['services.bakong.daily_check_limit' => 2]);

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 1,
                'responseMessage' => 'Transaction not found',
                'data' => null,
            ]),
        ]);

        $service = new BakongService();

        $service->checkTransactionByMd5('abc123');
        $service->checkTransactionByMd5('abc123');
        $service->checkTransactionByMd5('abc123');

        Http::assertSentCount(2);
    }

    public function test_check_transaction_by_md5_returns_null_instead_of_throwing_on_dns_failure(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::failedConnection('cURL error 6: Could not resolve host'),
        ]);

        $service = new BakongService();

        $result = $service->checkTransactionByMd5('abc123');

        $this->assertNull($result);
    }

    public function test_check_transaction_by_md5_ignores_budget_when_limit_is_zero(): void
    {
        $this->configureBakong(['services.bakong.daily_check_limit' => 0]);

        Http::fake([
            'https://api-bakong.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 1,
                'responseMessage' => 'Transaction not found',
                'data' => null,
            ]),
        ]);

        $service = new BakongService();

        $service->checkTransactionByMd5('abc123');
        $service->checkTransactionByMd5('abc123');
        $service->checkTransactionByMd5('abc123');

        Http::assertSentCount(3);
    }
}
