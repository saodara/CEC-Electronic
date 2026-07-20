<?php

namespace App\Services;

/**
 * Generates KHQR (EMV QR) strings locally following the NBC KHQR SDK specification.
 * No external API call required — everything is computed on the server.
 */
class KhqrGenerator
{
    private static function field(string $tag, string $value): string
    {
        return $tag . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    private static function crc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000)
                    ? (($crc << 1) ^ 0x1021) & 0xFFFF
                    : ($crc << 1) & 0xFFFF;
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Generate a dynamic individual KHQR string with a fixed amount.
     *
     * @return array{qr: string, md5: string}
     */
    public static function individual(
        string $accountId,
        string $merchantName,
        string $merchantCity = 'Phnom Penh',
        float  $amount = 0,
        string $currency = 'USD',
        string $billNumber = '',
        int    $expirationDays = 1
    ): array {
        $currencyCode = strtoupper($currency) === 'KHR' ? '116' : '840';

        // Tag 29 — individual account info
        $merchantAccount = self::field('29', self::field('00', $accountId));

        // Tag 62 — additional data (bill number)
        $additional = $billNumber
            ? self::field('62', self::field('01', substr($billNumber, 0, 25)))
            : '';

        // Tag 99 — KHQR timestamps in milliseconds
        $nowMs = (int) (microtime(true) * 1000);
        $expMs = $nowMs + ($expirationDays * 86_400_000);
        $timestamps = self::field('99',
            self::field('00', (string) $nowMs) .
            self::field('01', (string) $expMs)
        );

        // Amount string — strip trailing zeros after decimal
        $amountField = '';
        if ($amount > 0) {
            $formatted = number_format($amount, 2, '.', '');
            $amountField = self::field('54', rtrim(rtrim($formatted, '0'), '.'));
        }

        $qr  = self::field('00', '01');                          // Payload Format Indicator
        $qr .= self::field('01', '12');                          // Point of Initiation (dynamic)
        $qr .= $merchantAccount;                                  // Merchant Account
        $qr .= self::field('52', '5999');                        // MCC
        $qr .= self::field('53', $currencyCode);                 // Currency
        $qr .= $amountField;                                      // Amount
        $qr .= self::field('58', 'KH');                          // Country Code
        $qr .= self::field('59', mb_substr($merchantName, 0, 25)); // Merchant Name
        $qr .= self::field('60', mb_substr($merchantCity, 0, 15)); // Merchant City
        $qr .= $additional;                                       // Bill Number
        $qr .= $timestamps;                                       // Timestamps
        $qr .= '6304';                                            // CRC tag + length placeholder

        $crc    = self::crc16($qr);
        $qrFull = $qr . $crc;

        return [
            'qr'  => $qrFull,
            'md5' => md5($qrFull),
        ];
    }
}
