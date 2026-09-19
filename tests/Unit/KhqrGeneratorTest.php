<?php

namespace Tests\Unit;

use App\Services\KhqrGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class KhqrGeneratorTest extends TestCase
{
    public function test_generated_qr_starts_with_payload_format_and_dynamic_indicator(): void
    {
        $result = KhqrGenerator::individual('john@bank', 'John Merchant');

        $this->assertStringStartsWith('000201010212', $result['qr']);
    }

    public function test_qr_ends_with_a_four_character_uppercase_crc(): void
    {
        $result = KhqrGenerator::individual('john@bank', 'John Merchant');

        $this->assertMatchesRegularExpression('/[0-9A-F]{4}$/', $result['qr']);
    }

    public function test_md5_matches_the_full_qr_string(): void
    {
        $result = KhqrGenerator::individual('john@bank', 'John Merchant');

        $this->assertSame(md5($result['qr']), $result['md5']);
    }

    public function test_merchant_account_id_is_embedded_in_the_qr(): void
    {
        $result = KhqrGenerator::individual('unique_account_123', 'John Merchant');

        $this->assertStringContainsString('unique_account_123', $result['qr']);
    }

    #[DataProvider('currencyCases')]
    public function test_currency_code_is_mapped_correctly(string $currency, string $expectedCode): void
    {
        $result = KhqrGenerator::individual('john@bank', 'John Merchant', currency: $currency);

        $this->assertStringContainsString('53' . '03' . $expectedCode, $result['qr']);
    }

    public static function currencyCases(): array
    {
        return [
            'USD maps to 840' => ['USD', '840'],
            'KHR maps to 116' => ['KHR', '116'],
            'lowercase usd still maps to 840' => ['usd', '840'],
        ];
    }

    public function test_amount_field_is_omitted_when_amount_is_zero(): void
    {
        $result = KhqrGenerator::individual('john@bank', 'John Merchant', amount: 0);

        $this->assertArrayNotHasKey('54', $this->parseTlvFields($result['qr']));
    }

    #[DataProvider('amountFormattingCases')]
    public function test_amount_trailing_zeros_are_stripped(float $amount, string $expectedField): void
    {
        $result = KhqrGenerator::individual('john@bank', 'John Merchant', amount: $amount);

        $this->assertStringContainsString('54' . str_pad((string) strlen($expectedField), 2, '0', STR_PAD_LEFT) . $expectedField, $result['qr']);
    }

    public static function amountFormattingCases(): array
    {
        return [
            'whole number strips .00' => [10.00, '10'],
            'one decimal strips trailing zero' => [10.50, '10.5'],
            'two decimals kept as-is' => [10.55, '10.55'],
        ];
    }

    public function test_merchant_name_longer_than_25_chars_is_truncated(): void
    {
        $longName = str_repeat('A', 40);

        $result = KhqrGenerator::individual('john@bank', $longName);

        $this->assertStringContainsString('59' . '25' . str_repeat('A', 25), $result['qr']);
    }

    public function test_bill_number_is_included_when_provided(): void
    {
        $result = KhqrGenerator::individual('john@bank', 'John Merchant', billNumber: 'INV-001');

        $this->assertStringContainsString('INV-001', $result['qr']);
    }

    public function test_bill_number_is_omitted_when_blank(): void
    {
        $result = KhqrGenerator::individual('john@bank', 'John Merchant', billNumber: '');

        $this->assertArrayNotHasKey('62', $this->parseTlvFields($result['qr']));
    }

    public function test_expiration_is_set_in_seconds_in_the_timestamp_tag(): void
    {
        $before = time();
        $result = KhqrGenerator::individual('john@bank', 'John Merchant', expirationSeconds: 90);

        $timestamps = $this->parseTlvFields($this->parseTlvFields($result['qr'])['99']);
        $createdMs = (int) $timestamps['00'];
        $expiresMs = (int) $timestamps['01'];

        $this->assertSame(90 * 1000, $expiresMs - $createdMs);
        $this->assertGreaterThanOrEqual($before + 90, $result['expires_at']);
        $this->assertSame(intdiv($expiresMs, 1000), $result['expires_at']);
    }

    public function test_expiration_defaults_to_one_day(): void
    {
        $result = KhqrGenerator::individual('john@bank', 'John Merchant');

        $timestamps = $this->parseTlvFields($this->parseTlvFields($result['qr'])['99']);

        $this->assertSame(86400 * 1000, (int) $timestamps['01'] - (int) $timestamps['00']);
    }

    /**
     * Decode a tag-length-value (EMV QR) string into a flat [tag => value] map.
     *
     * @return array<string, string>
     */
    private function parseTlvFields(string $data): array
    {
        $fields = [];
        $position = 0;
        $length = strlen($data);

        while ($position < $length) {
            $tag = substr($data, $position, 2);
            $valueLength = (int) substr($data, $position + 2, 2);
            $value = substr($data, $position + 4, $valueLength);

            $fields[$tag] = $value;
            $position += 4 + $valueLength;
        }

        return $fields;
    }
}
