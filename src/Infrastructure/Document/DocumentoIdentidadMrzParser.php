<?php

declare(strict_types=1);

namespace App\Infrastructure\Document;

/**
 * Parser MRZ TD1 (DNI/NIE español, 3 líneas × 30 caracteres).
 */
final class DocumentoIdentidadMrzParser
{
    /**
     * @return array{
     *   nombre: string,
     *   nacionalidad: string,
     *   tipoDocumento: string,
     *   numDocumento: string,
     *   fechaNacimiento: string|null,
     *   lugarNacimiento: string,
     *   extraccionAutomatica: bool
     * }|null
     */
    public function parseFromText(string $text): ?array
    {
        $lines = $this->extractTd1Lines($text);
        if (null === $lines) {
            return null;
        }

        [$line1, $line2, $line3] = $lines;

        $tipoCodigo = substr($line1, 0, 2);
        $paisEmisor = substr($line1, 2, 3);
        $numDocumento = $this->extractNumDocumentoLinea1($line1);

        $fechaNac = $this->parseMrzDate(substr($line2, 0, 6));
        $nacionalidad = rtrim(substr($line2, 15, 3), '<');

        $nombres = $this->parseNombres($line3);

        if ('' === $numDocumento && '' === $nombres['nombre']) {
            return null;
        }

        $tipoDocumento = $this->inferTipoDocumento($numDocumento, $tipoCodigo);

        return [
            'nombre' => $nombres['nombre'],
            'nacionalidad' => $this->nacionalidadLabel($nacionalidad !== '' ? $nacionalidad : $paisEmisor),
            'tipoDocumento' => $tipoDocumento,
            'numDocumento' => $this->normalizarNumDocumento($numDocumento, $tipoDocumento),
            'fechaNacimiento' => $fechaNac,
            'lugarNacimiento' => '',
            'extraccionAutomatica' => true,
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}|null
     */
    private function extractTd1Lines(string $text): ?array
    {
        $normalized = strtoupper($text);
        $normalized = str_replace([' ', "\t"], '', $normalized);

        if (preg_match_all('/[A-Z0-9<]{30}/', $normalized, $matches) && count($matches[0]) >= 3) {
            foreach ($matches[0] as $i => $line) {
                if ($i + 2 >= count($matches[0])) {
                    break;
                }
                $l1 = $matches[0][$i];
                $l2 = $matches[0][$i + 1];
                $l3 = $matches[0][$i + 2];
                if ($this->looksLikeSpanishIdMrz($l1, $l2)) {
                    return [$l1, $l2, $l3];
                }
            }
        }

        $rawLines = preg_split('/\R/', $normalized) ?: [];
        $candidates = [];
        foreach ($rawLines as $line) {
            $line = preg_replace('/[^A-Z0-9<]/', '', $line) ?? '';
            if (30 === strlen($line)) {
                $candidates[] = $line;
            }
        }

        for ($i = 0; $i + 2 < count($candidates); ++$i) {
            if ($this->looksLikeSpanishIdMrz($candidates[$i], $candidates[$i + 1])) {
                return [$candidates[$i], $candidates[$i + 1], $candidates[$i + 2]];
            }
        }

        return null;
    }

    private function looksLikeSpanishIdMrz(string $line1, string $line2): bool
    {
        $prefix = substr($line1, 0, 5);

        return str_starts_with($prefix, 'IDESP')
            || str_starts_with($prefix, 'IDES')
            || (str_starts_with(substr($line1, 0, 2), 'ID') && str_contains($line1, 'ESP'))
            || (bool) preg_match('/^[A-Z]{2}[A-Z<]{3}/', $line1);
    }

    /**
     * @return array{nombre: string}
     */
    private function parseNombres(string $line3): array
    {
        $parts = explode('<<', trim($line3, '<'));
        $apellidos = str_replace('<', ' ', $parts[0] ?? '');
        $nombre = str_replace('<', ' ', $parts[1] ?? '');

        $apellidos = trim(preg_replace('/\s+/', ' ', $apellidos) ?? '');
        $nombre = trim(preg_replace('/\s+/', ' ', $nombre) ?? '');

        $completo = trim($nombre . ' ' . $apellidos);

        return ['nombre' => $completo];
    }

    private function parseMrzDate(string $yymmdd): ?string
    {
        if (!preg_match('/^\d{6}$/', $yymmdd)) {
            return null;
        }

        $yy = (int) substr($yymmdd, 0, 2);
        $mm = substr($yymmdd, 2, 2);
        $dd = substr($yymmdd, 4, 2);

        $year = $yy > 30 ? 1900 + $yy : 2000 + $yy;

        return sprintf('%04d-%s-%s', $year, $mm, $dd);
    }

    /**
     * TD1 español (IDESP): pos. 6-14 = número de soporte (BKT…); pos. 15-23 = DNI/NIE.
     * El parser genérico TD1 usa 6-14 como documento, lo cual en España devuelve el soporte.
     */
    private function extractNumDocumentoLinea1(string $line1): string
    {
        if ($this->isSpanishIdLine1($line1)) {
            $trasCabecera = substr($line1, 5);

            if (preg_match('/(\d{8}[A-Z])/', $trasCabecera, $m)) {
                return $m[1];
            }
            if (preg_match('/([XYZ]\d{7}[A-Z])/', $trasCabecera, $m)) {
                return $m[1];
            }

            $campoFijo = str_replace('<', '', rtrim(substr($line1, 14, 9), '<'));
            if ($this->looksLikeDniNie($campoFijo)) {
                return $campoFijo;
            }

            return '';
        }

        $generico = rtrim(substr($line1, 5, 9), '<');
        if ($this->isNumeroSoporteEspanol($generico)) {
            return '';
        }

        return $generico;
    }

    private function isSpanishIdLine1(string $line1): bool
    {
        return str_starts_with($line1, 'IDESP')
            || str_starts_with($line1, 'IDESM')
            || (str_starts_with($line1, 'ID') && str_contains(substr($line1, 2, 3), 'ESP'));
    }

    private function looksLikeDniNie(string $num): bool
    {
        $num = strtoupper(str_replace('<', '', $num));

        return (bool) preg_match('/^\d{8}[A-Z]$/', $num)
            || (bool) preg_match('/^[XYZ]\d{7}[A-Z]$/', $num);
    }

    private function isNumeroSoporteEspanol(string $value): bool
    {
        return (bool) preg_match('/^[A-Z]{3}\d{6,7}$/', strtoupper(rtrim($value, '<')));
    }

    private function inferTipoDocumento(string $numDocumento, string $tipoCodigo): string
    {
        $num = strtoupper($numDocumento);
        if (preg_match('/^[XYZ]\d{7}[A-Z]$/', $num)) {
            return 'NIE';
        }
        if (preg_match('/^\d{8}[A-Z]$/', $num)) {
            return 'DNI';
        }

        return str_starts_with($tipoCodigo, 'P') ? 'PASAPORTE' : 'DNI';
    }

    private function normalizarNumDocumento(string $numDocumento, string $tipoDocumento): string
    {
        $num = strtoupper(rtrim($numDocumento, '<'));
        if ('DNI' === $tipoDocumento && preg_match('/^(\d{1,8})([A-Z])$/', $num, $m)) {
            return str_pad($m[1], 8, '0', STR_PAD_LEFT) . $m[2];
        }

        return $num;
    }

    private function nacionalidadLabel(string $codigo): string
    {
        return match ($codigo) {
            'ESP' => 'Española',
            default => $codigo,
        };
    }
}
