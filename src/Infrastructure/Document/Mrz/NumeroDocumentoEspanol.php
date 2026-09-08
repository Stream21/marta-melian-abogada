<?php

declare(strict_types=1);

namespace App\Infrastructure\Document\Mrz;

/**
 * Validación y corrección de DNI/NIE mediante su letra de control (módulo 23).
 *
 * Permite descartar lecturas OCR imposibles y recuperar la correcta cuando solo
 * hay confusión de glifos (0/O, 1/I, 5/S, 8/B…).
 */
final class NumeroDocumentoEspanol
{
    private const LETRAS = 'TRWAGMYFPDXBNJZSQVHLCKE';
    private const PREFIJOS_NIE = ['X' => '0', 'Y' => '1', 'Z' => '2'];

    public static function esDni(string $valor): bool
    {
        $v = self::limpiar($valor);
        if (!preg_match('/^(\d{8})([A-Z])$/', $v, $m)) {
            return false;
        }

        return self::letraControl((int) $m[1]) === $m[2];
    }

    public static function esNie(string $valor): bool
    {
        $v = self::limpiar($valor);
        if (!preg_match('/^([XYZ])(\d{7})([A-Z])$/', $v, $m)) {
            return false;
        }

        return self::letraControl((int) (self::PREFIJOS_NIE[$m[1]] . $m[2])) === $m[3];
    }

    public static function esValido(string $valor): bool
    {
        return self::esDni($valor) || self::esNie($valor);
    }

    /** Cumple el formato aunque la letra de control no cuadre. */
    public static function tieneFormato(string $valor): bool
    {
        $v = self::limpiar($valor);

        return 1 === preg_match('/^\d{8}[A-Z]$/', $v) || 1 === preg_match('/^[XYZ]\d{7}[A-Z]$/', $v);
    }

    /**
     * Devuelve la lectura corregida si alguna variante OCR valida el módulo 23.
     * Si ninguna valida, devuelve la entrada normalizada cuando tiene formato, o null.
     */
    public static function corregir(string $valor): ?string
    {
        $v = self::limpiar($valor);
        if ('' === $v) {
            return null;
        }

        if (self::esValido($v)) {
            return $v;
        }

        foreach (self::variantes($v) as $candidato) {
            if (self::esValido($candidato)) {
                return $candidato;
            }
        }

        return self::tieneFormato($v) ? $v : null;
    }

    public static function letraControl(int $numero): string
    {
        return self::LETRAS[$numero % 23];
    }

    private static function limpiar(string $valor): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $valor) ?? '');
    }

    /**
     * Variantes con los glifos ambiguos corregidos según su posición
     * (los 8/7 primeros caracteres son dígitos; el último, letra).
     *
     * @return list<string>
     */
    private static function variantes(string $valor): array
    {
        $largo = strlen($valor);
        if ($largo < 8 || $largo > 10) {
            return [];
        }

        $candidatos = [];

        // Hipótesis DNI: 8 dígitos + letra.
        if (9 === $largo) {
            $candidatos[] = MrzOcrCorrector::aDigitos(substr($valor, 0, 8))
                . MrzOcrCorrector::aLetras(substr($valor, 8, 1));
        }

        // Hipótesis NIE: prefijo XYZ + 7 dígitos + letra.
        if (9 === $largo) {
            $prefijo = MrzOcrCorrector::aLetras(substr($valor, 0, 1));
            $prefijo = match ($prefijo) {
                'X', 'Y', 'Z' => $prefijo,
                'K', 'H' => 'X',
                default => $prefijo,
            };
            $candidatos[] = $prefijo
                . MrzOcrCorrector::aDigitos(substr($valor, 1, 7))
                . MrzOcrCorrector::aLetras(substr($valor, 8, 1));
        }

        // Lecturas con un carácter de más o de menos por ruido en los extremos.
        if (10 === $largo) {
            $candidatos[] = substr($valor, 0, 9);
            $candidatos[] = substr($valor, 1, 9);
        }
        if (8 === $largo && ctype_digit(MrzOcrCorrector::aDigitos(substr($valor, 0, 7)))) {
            $base = MrzOcrCorrector::aDigitos(substr($valor, 0, 7));
            $letra = MrzOcrCorrector::aLetras(substr($valor, 7, 1));
            $candidatos[] = '0' . $base . $letra;
        }

        return array_values(array_unique(array_filter(
            $candidatos,
            static fn (string $c): bool => '' !== $c && $c !== $valor,
        )));
    }
}
