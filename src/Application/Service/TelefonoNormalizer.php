<?php

declare(strict_types=1);

namespace App\Application\Service;

final class TelefonoNormalizer
{
    private const string DEFAULT_COUNTRY_CODE = '34';

    public function normalize(?string $telefono): ?string
    {
        if (null === $telefono) {
            return null;
        }

        $trimmed = trim($telefono);
        if ('' === $trimmed) {
            return null;
        }

        $collapsed = preg_replace('/[\s\-().]/', '', $trimmed) ?? $trimmed;

        return $this->toE164($collapsed);
    }

    public function isValid(?string $telefono): bool
    {
        $normalized = $this->normalize($telefono);

        return null !== $normalized && 1 === preg_match('/^\+[1-9]\d{6,14}$/', $normalized);
    }

    private function toE164(string $value): ?string
    {
        if (str_starts_with($value, '+')) {
            $digits = preg_replace('/\D/', '', substr($value, 1)) ?? '';
            if ('' === $digits) {
                return null;
            }

            return '+' . $digits;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        if ('' === $digits) {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            if ('' === $digits) {
                return null;
            }

            return '+' . $digits;
        }

        if (
            str_starts_with($digits, self::DEFAULT_COUNTRY_CODE)
            && 11 === strlen($digits)
            && $this->isSpanishMobile(substr($digits, 2))
        ) {
            return '+' . $digits;
        }

        if ($this->isSpanishMobile($digits)) {
            return '+' . self::DEFAULT_COUNTRY_CODE . $digits;
        }

        if (strlen($digits) >= 10 && strlen($digits) <= 15) {
            return '+' . $digits;
        }

        return null;
    }

    private function isSpanishMobile(string $digits): bool
    {
        return 1 === preg_match('/^[67]\d{8}$/', $digits);
    }
}
