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
            'services.bakong.relay_url' => 'https://relay.test',
            'services.bakong.account_id' => '855012345678',
            'services.bakong.token' => 'test-token',
            'services.bakong.merchant_name' => 'CEC Electronic',
            'services.bakong.merchant_city' => 'Phnom Penh',
        ], $overrides));
    }

    public function test_is_configured_is_false_when_account_id_is_blank(): void
    {
        config(['services.bakong.account_id' => '']);

        $service = new BakongService();

        $this->assertFalse($service->isConfigured());
    }

    public function test_is_configured_is_true_when_account_id_is_set(): void
    {
        config(['services.bakong.account_id' => '855012345678']);

        $service = new BakongService();

        $this->assertTrue($service->isConfigured());
    }

    public function test_generate_qr_for_order_returns_null_when_not_configured(): void
    {
        config(['services.bakong.account_id' => '']);
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
        // The order's account id, amount and order number are wired into the QR payload.
        $this->assertStringContainsString('855012345678', $result['qr']);
        $this->assertStringContainsString('12.34', $result['qr']);
        $this->assertStringContainsString('EH-20260906-9999', $result['qr']);
    }

    public function test_generate_image_returns_null_when_relay_url_not_configured(): void
    {
        config(['services.bakong.relay_url' => '']);
        $service = new BakongService();

        $result = $service->generateImage('some-qr-string');

        $this->assertNull($result);
    }

    public function test_generate_image_returns_base64_image_on_success(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://relay.test/v1/generate_khqr_image' => Http::response([
                'responseCode' => 0,
                'data' => ['image' => 'data:image/png;base64,AAAA'],
            ]),
        ]);

        $service = new BakongService();

        $result = $service->generateImage('some-qr-string');

        $this->assertSame('data:image/png;base64,AAAA', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://relay.test/v1/generate_khqr_image'
                && $request['qr'] === 'some-qr-string';
        });
    }

    public function test_generate_image_returns_null_on_nonzero_response_code(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://relay.test/v1/generate_khqr_image' => Http::response([
                'responseCode' => 1,
                'data' => ['image' => null],
            ]),
        ]);

        $service = new BakongService();

        $result = $service->generateImage('some-qr-string');

        $this->assertNull($result);
    }

    public function test_generate_image_returns_null_on_unsuccessful_response(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://relay.test/v1/generate_khqr_image' => Http::response(['error' => 'boom'], 500),
        ]);

        $service = new BakongService();

        $result = $service->generateImage('some-qr-string');

        $this->assertNull($result);
    }

    public function test_create_web_checkout_returns_null_when_not_configured(): void
    {
        config(['services.bakong.account_id' => '']);
        $service = new BakongService();

        $result = $service->createWebCheckout($this->order(), 'https://shop.test/return', 'https://shop.test/webhook');

        $this->assertNull($result);
    }

    public function test_create_web_checkout_posts_expected_payload_and_returns_data_on_success(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://relay.test/v1/web_checkouts/create' => Http::response([
                'responseCode' => 0,
                'data' => ['session_id' => 'sess-123', 'checkout_url' => 'https://relay.test/checkout/sess-123'],
            ]),
        ]);

        $service = new BakongService();

        $order = $this->order([
            'order_number' => 'EH-20260906-4321',
            'grand_total' => 88.0,
        ]);

        $result = $service->createWebCheckout($order, 'https://shop.test/return', 'https://shop.test/webhook');

        $this->assertSame([
            'session_id' => 'sess-123',
            'checkout_url' => 'https://relay.test/checkout/sess-123',
        ], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://relay.test/v1/web_checkouts/create'
                && $request['trans_id'] === 'EH-20260906-4321'
                && $request['req_khqr']['account_id'] === '855012345678'
                && $request['req_khqr']['merchant_name'] === 'CEC Electronic'
                && $request['req_khqr']['merchant_city'] === 'Phnom Penh'
                && $request['req_khqr']['amount'] === 88.0
                && $request['req_khqr']['currency'] === 'USD'
                && $request['req_url']['return_url'] === 'https://shop.test/return'
                && $request['req_url']['webhook_url'] === 'https://shop.test/webhook'
                && $request->hasHeader('Authorization', 'Bearer test-token');
        });
    }

    public function test_create_web_checkout_returns_null_on_failure(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://relay.test/v1/web_checkouts/create' => Http::response([
                'responseCode' => 1,
                'message' => 'failed',
            ]),
        ]);

        $service = new BakongService();

        $result = $service->createWebCheckout($this->order(), 'https://shop.test/return', 'https://shop.test/webhook');

        $this->assertNull($result);
    }

    public function test_get_checkout_details_returns_null_when_relay_url_blank(): void
    {
        config(['services.bakong.relay_url' => '']);
        $service = new BakongService();

        $result = $service->getCheckoutDetails('sess-123');

        $this->assertNull($result);
    }

    public function test_get_checkout_details_returns_data_on_success(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://relay.test/v1/web_checkouts/details' => Http::response([
                'responseCode' => 0,
                'data' => ['status' => 'PAID'],
            ]),
        ]);

        $service = new BakongService();

        $result = $service->getCheckoutDetails('sess-123');

        $this->assertSame(['status' => 'PAID'], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://relay.test/v1/web_checkouts/details'
                && $request['session_id'] === 'sess-123';
        });
    }

    public function test_get_checkout_details_returns_null_on_failure(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://relay.test/v1/web_checkouts/details' => Http::response(['responseCode' => 1]),
        ]);

        $service = new BakongService();

        $result = $service->getCheckoutDetails('sess-123');

        $this->assertNull($result);
    }

    public function test_check_transaction_by_md5_returns_null_when_relay_url_blank(): void
    {
        config(['services.bakong.relay_url' => '']);
        $service = new BakongService();

        $result = $service->checkTransactionByMd5('abc123');

        $this->assertNull($result);
    }

    public function test_check_transaction_by_md5_returns_data_on_success(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://relay.test/v1/check_transaction_by_md5' => Http::response([
                'responseCode' => 0,
                'data' => ['hash' => 'abc123', 'amount' => 12.34],
            ]),
        ]);

        $service = new BakongService();

        $result = $service->checkTransactionByMd5('abc123');

        $this->assertSame(['hash' => 'abc123', 'amount' => 12.34], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://relay.test/v1/check_transaction_by_md5'
                && $request['md5'] === 'abc123';
        });
    }

    public function test_check_transaction_by_md5_returns_null_on_failure(): void
    {
        $this->configureBakong();

        Http::fake([
            'https://relay.test/v1/check_transaction_by_md5' => Http::response([], 404),
        ]);

        $service = new BakongService();

        $result = $service->checkTransactionByMd5('abc123');

        $this->assertNull($result);
    }
}
