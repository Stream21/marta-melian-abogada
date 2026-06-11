<?php

declare(strict_types=1);

namespace App\Infrastructure\Document;

/**
 * Extrae campos visibles del reverso del DNI/NIE (no MRZ).
 */
final class DocumentoIdentidadReversoParser
{
    /**
     * @return array{
     *   domicilio: string,
     *   codigoPostal: string,
     *   ciudad: string,
     *   provincia: string,
     *   lugarNacimiento: string,
     *   nombrePadre: string,
     *   nombreMadre: string
     * }
     */
    public function parseFromText(string $text): array
    {
        $domicilioRaw = $this->extractCampo($text, 'DOMICILIO', 'LUGAR\\s*DE\\s*NACIMIENTO|HIJ[OA]|EQUIPO|IDESP');
        $partesDomicilio = $this->parseDomicilioPartes($domicilioRaw);

        $filiacion = $this->extractFiliacion($text);

        return [
            'domicilio' => $partesDomicilio['domicilio'],
            'codigoPostal' => '',
            'ciudad' => $partesDomicilio['ciudad'],
            'provincia' => $partesDomicilio['provincia'],
            'lugarNacimiento' => $this->extractCampo($text, 'LUGAR\\s*DE\\s*NACIMIENTO', 'HIJ[OA]|EQUIPO|IDESP'),
            'nombrePadre' => $filiacion['padre'],
            'nombreMadre' => $filiacion['madre'],
        ];
    }

    private function extractCampo(string $text, string $etiqueta, string $siguienteEtiqueta): string
    {
        // Delimitador # (no /): la etiqueta HIJO/A DE contiene barras literales.
        $pattern = sprintf(
            '#%s\s*:?\s*(.+?)(?=(?:%s)|$)#is',
            $etiqueta,
            $siguienteEtiqueta,
        );

        if (!preg_match($pattern, $text, $matches)) {
            return '';
        }

        $valor = trim(preg_replace('/\s+/', ' ', $matches[1]) ?? '');
        $valor = rtrim($valor, '.');

        return $valor;
    }

    /**
     * @return array{domicilio: string, ciudad: string, provincia: string}
     */
    private function parseDomicilioPartes(string $domicilio): array
    {
        if ('' === $domicilio) {
            return ['domicilio' => '', 'ciudad' => '', 'provincia' => ''];
        }

        $partes = array_values(array_filter(array_map('trim', explode(',', $domicilio)), static fn (string $p): bool => '' !== $p));

        if (count($partes) >= 3) {
            $provincia = array_pop($partes);
            $ciudad = array_pop($partes);

            return [
                'domicilio' => implode(', ', $partes),
                'ciudad' => $ciudad,
                'provincia' => $provincia,
            ];
        }

        if (2 === count($partes)) {
            return [
                'domicilio' => $partes[0],
                'ciudad' => $partes[1],
                'provincia' => '',
            ];
        }

        return ['domicilio' => $domicilio, 'ciudad' => '', 'provincia' => ''];
    }

    /**
     * @return array{padre: string, madre: string}
     */
    private function extractFiliacion(string $text): array
    {
        $raw = $this->extractCampo($text, 'HIJ[OA]\\s*/?\\s*A?\\s*DE', 'EQUIPO|IDESP|<<');
        if ('' === $raw) {
            return ['padre' => '', 'madre' => ''];
        }

        $partes = preg_split('/\s*\/\s*/', $raw) ?: [];

        return [
            'padre' => trim($partes[0] ?? ''),
            'madre' => trim($partes[1] ?? ''),
        ];
    }
}
