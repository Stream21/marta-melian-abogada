<?php

declare(strict_types=1);

namespace App\Infrastructure\Document\Mrz;

/**
 * Dígito de control ICAO 9303 (pesos 7-3-1).
 */
final class MrzCheckDigit
{
    private const PESOS = [7, 3, 1];

    public static function compute(string $value): int
    {
        $suma = 0;
        $len = strlen($value);

        for ($i = 0; $i < $len; ++$i) {
            $suma += self::valorCaracter($value[$i]) * self::PESOS[$i % 3];
        }

        return $suma % 10;
    }

    /** Un dígito no numérico se considera ilegible: no valida. */
    public static function matches(string $value, string $digito): bool
    {
        if (1 !== strlen($digito) || !ctype_digit($digito)) {
            return false;
        }

        return self::compute($value) === (int) $digito;
    }

    /**
     * Relleno ICAO: un campo compuesto solo por '<' tiene dígito '0' o '<'.
     */
    public static function matchesOpcional(string $value, string $digito): bool
    {
        if ('' === trim($value, '<')) {
            return '<' === $digito || '0' === $digito;
        }

        return self::matches($value, $digito);
    }

    private static function valorCaracter(string $c): int
    {
        if ('<' === $c) {
            return 0;
        }
        if ($c >= '0' && $c <= '9') {
            return (int) $c;
        }
        if ($c >= 'A' && $c <= 'Z') {
            return ord($c) - 55;
        }

        return 0;
    }
}
