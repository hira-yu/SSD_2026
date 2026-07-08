<?php

declare(strict_types=1);

class PaymentService
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function normalizeCardInput(array $input): array
    {
        $rawExpiryMonth = trim((string) ($input['card_expiry_month'] ?? ''));
        $expiryMonth = $rawExpiryMonth === '' ? '' : str_pad($rawExpiryMonth, 2, '0', STR_PAD_LEFT);
        $expiryYear = trim((string) ($input['card_expiry_year'] ?? ''));
        $legacyExpiry = strtoupper(trim((string) ($input['card_expiry'] ?? '')));

        $cardExpiry = $legacyExpiry;

        if ($expiryMonth !== '' && $expiryYear !== '') {
            $cardExpiry = $expiryMonth . '/' . $expiryYear;
        }

        return [
            'card_number' => preg_replace('/\s+/', '', trim((string) ($input['card_number'] ?? ''))) ?? '',
            'cardholder_name' => trim((string) ($input['cardholder_name'] ?? '')),
            'card_expiry' => $cardExpiry,
            'card_expiry_month' => $expiryMonth,
            'card_expiry_year' => $expiryYear,
            'security_code' => trim((string) ($input['security_code'] ?? '')),
        ];
    }

    /**
     * @param array<string, string> $card
     * @return array<int, string>
     */
    public function validateCardInput(array $card): array
    {
        $errors = [];

        if (!preg_match('/^\d{13,19}$/', $card['card_number'])) {
            $errors[] = 'カード番号は数字のみ13〜19桁で入力してください。';
        }

        if ($card['cardholder_name'] === '') {
            $errors[] = '名義人を入力してください。';
        }

        if (!$this->isValidExpiry($card['card_expiry'])) {
            $errors[] = '有効期限を正しく入力してください。';
        }

        if (!preg_match('/^\d{3,4}$/', $card['security_code'])) {
            $errors[] = 'セキュリティコードは数字3〜4桁で入力してください。';
        }

        return $errors;
    }

    /**
     * @param array<string, string> $card
     * @return array<string, string>
     */
    public function buildCardSummary(array $card): array
    {
        return [
            'masked_card_number' => $this->maskCardNumber($card['card_number']),
            'cardholder_name' => $card['cardholder_name'],
            'card_expiry' => $card['card_expiry'],
            'validation_result' => '疑似決済の形式チェックを通過しました。',
        ];
    }

    public function maskCardNumber(string $cardNumber): string
    {
        $last4 = substr($cardNumber, -4);
        $maskedLength = max(strlen($cardNumber) - 4, 0);

        return str_repeat('*', $maskedLength) . $last4;
    }

    private function isValidExpiry(string $expiry): bool
    {
        if (preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $matches) !== 1
            && preg_match('/^(0[1-9]|1[0-2])\/(\d{4})$/', $expiry, $matches) !== 1) {
            return false;
        }

        $month = (int) $matches[1];
        $year = strlen($matches[2]) === 2 ? 2000 + (int) $matches[2] : (int) $matches[2];
        $expiresAt = DateTimeImmutable::createFromFormat('Y-n-j H:i:s', sprintf('%d-%d-1 00:00:00', $year, $month));

        if (!$expiresAt instanceof DateTimeImmutable) {
            return false;
        }

        $endOfMonth = $expiresAt->modify('last day of this month')->setTime(23, 59, 59);

        return $endOfMonth >= new DateTimeImmutable('now');
    }
}
