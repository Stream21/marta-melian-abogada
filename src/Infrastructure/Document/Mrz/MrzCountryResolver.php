<?php

declare(strict_types=1);

namespace App\Infrastructure\Document\Mrz;

use App\Infrastructure\Persistence\Migration\NacionalidadSeedData;

/**
 * Resuelve el código ISO3 de la MRZ a un código válido, corrigiendo errores de OCR.
 *
 * Nunca devuelve un código inventado: si no se puede resolver con confianza,
 * devuelve null para que el campo quede vacío en lugar de mostrar basura («ES7»).
 */
final class MrzCountryResolver
{
    /** Códigos ICAO que no son ISO3 de país pero sí válidos en MRZ. */
    private const ALIAS = [
        'D' => 'DEU',
        'GBD' => 'GBR',
        'GBN' => 'GBR',
        'GBO' => 'GBR',
        'GBP' => 'GBR',
        'GBS' => 'GBR',
        'UNK' => 'XXK',
        'RKS' => 'XXK',
    ];

    /** Resuelve a código ISO3 válido o null. */
    public static function resolverCodigo(string $raw): ?string
    {
        $codigo = strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($raw)) ?? '');
        if ('' === $codigo) {
            return null;
        }

        if (isset(self::ALIAS[$codigo])) {
            $codigo = self::ALIAS[$codigo];
        }

        $validos = self::codigosValidos();

        if (isset($validos[$codigo])) {
            return $codigo;
        }

        if (3 !== strlen($codigo)) {
            return null;
        }

        // Variantes por confusión de glifos (ES7 → ESP, ESQ → ESP…).
        foreach (MrzOcrCorrector::variantesAlfabeticas($codigo) as $variante) {
            if (isset($validos[$variante])) {
                return $variante;
            }
        }

        // Último recurso solo si hay rastro de error de OCR (algún dígito donde debería
        // haber una letra): un código de tres letras limpio que no está en el catálogo
        // es un país no soportado, no una lectura corrupta que haya que adivinar.
        if (1 !== preg_match('/\d/', $codigo)) {
            return null;
        }

        $candidatos = [];
        foreach (array_keys($validos) as $valido) {
            if (3 === strlen($valido) && 1 === levenshtein($codigo, $valido)) {
                $candidatos[] = $valido;
            }
        }

        return 1 === count($candidatos) ? $candidatos[0] : null;
    }

    /** Gentilicio en español, o null si el código no se pudo resolver. */
    public static function resolverNacionalidad(string $raw): ?string
    {
        $codigo = self::resolverCodigo($raw);

        return null === $codigo ? null : (NacionalidadSeedData::nombrePorCodigo($codigo) ?? null);
    }

    /**
     * @return array<string, string>
     */
    private static function codigosValidos(): array
    {
        static $mapa = null;

        if (null === $mapa) {
            $mapa = NacionalidadSeedData::mapa();
        }

        return $mapa;
    }
}
