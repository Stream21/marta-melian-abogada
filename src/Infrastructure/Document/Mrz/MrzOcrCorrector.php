<?php

declare(strict_types=1);

namespace App\Infrastructure\Document\Mrz;

/**
 * Corrige confusiones típicas de OCR en banda MRZ según el tipo esperado de cada campo.
 *
 * La MRZ tiene posiciones fijas: en los campos numéricos (fechas, dígitos de control)
 * una «O» solo puede ser un cero, y en los alfabéticos (país, nacionalidad, nombre)
 * un «7» solo puede ser una letra. Aplicar la corrección por posición evita valores
 * corruptos como «ES7» en lugar de «ESP».
 */
final class MrzOcrCorrector
{
    /** Glifos que el OCR confunde con cada dígito. */
    private const HACIA_DIGITO = [
        'O' => '0', 'Q' => '0', 'D' => '0', 'U' => '0', 'C' => '0',
        'I' => '1', 'L' => '1', 'J' => '1', '|' => '1',
        'Z' => '2',
        'A' => '4',
        'S' => '5',
        'G' => '6',
        'T' => '7',
        'B' => '8',
    ];

    /** Glifos que el OCR confunde con cada letra. */
    private const HACIA_LETRA = [
        '0' => 'O',
        '1' => 'I',
        '2' => 'Z',
        '4' => 'A',
        '5' => 'S',
        '6' => 'G',
        '7' => 'P',
        '8' => 'B',
        '9' => 'G',
    ];

    /** Alternativas adicionales para búsqueda difusa (código de país, etc.). */
    private const ALTERNATIVAS_LETRA = [
        '0' => ['O', 'D', 'Q', 'U'],
        '1' => ['I', 'L', 'T'],
        '2' => ['Z'],
        '4' => ['A'],
        '5' => ['S'],
        '6' => ['G'],
        '7' => ['P', 'T', 'Z'],
        '8' => ['B', 'S'],
        '9' => ['G', 'P', 'Q'],
    ];

    /** Limpieza global previa: mayúsculas, chevrones y ruido fuera. */
    public static function normalizarTexto(string $text): string
    {
        $t = strtoupper($text);
        $t = str_replace(['«', '»', '＜', '‹', '›'], '<', $t);
        $t = str_replace(['|', '¦'], 'I', $t);
        $t = str_replace(['°', 'º'], '0', $t);
        $t = preg_replace('/[^A-Z0-9<\r\n]/', ' ', $t) ?? '';

        return preg_replace('/[ \t]+/', ' ', $t) ?? '';
    }

    /** Fuerza dígitos en un campo numérico (fechas, dígitos de control). */
    public static function aDigitos(string $value): string
    {
        $out = '';
        $len = strlen($value);

        for ($i = 0; $i < $len; ++$i) {
            $c = $value[$i];
            $out .= self::HACIA_DIGITO[$c] ?? $c;
        }

        return $out;
    }

    /** Fuerza letras (o relleno '<') en un campo alfabético: país, nacionalidad, nombre. */
    public static function aLetras(string $value): string
    {
        $out = '';
        $len = strlen($value);

        for ($i = 0; $i < $len; ++$i) {
            $c = $value[$i];
            if ('<' === $c) {
                $out .= $c;
                continue;
            }
            $out .= self::HACIA_LETRA[$c] ?? $c;
        }

        return $out;
    }

    /**
     * Variantes plausibles de un código alfabético corto (p. ej. «ES7» → «ESP»).
     *
     * @return list<string>
     */
    public static function variantesAlfabeticas(string $value, int $maxVariantes = 24): array
    {
        $base = strtoupper(trim($value));
        if ('' === $base) {
            return [];
        }

        $variantes = [$base];

        $len = strlen($base);
        for ($i = 0; $i < $len; ++$i) {
            $c = $base[$i];
            $alternativas = self::ALTERNATIVAS_LETRA[$c] ?? null;
            if (null === $alternativas) {
                continue;
            }

            foreach ($variantes as $variante) {
                foreach ($alternativas as $reemplazo) {
                    $nueva = substr_replace($variante, $reemplazo, $i, 1);
                    if (!in_array($nueva, $variantes, true)) {
                        $variantes[] = $nueva;
                    }
                    if (count($variantes) >= $maxVariantes) {
                        return $variantes;
                    }
                }
            }
        }

        return $variantes;
    }
}
