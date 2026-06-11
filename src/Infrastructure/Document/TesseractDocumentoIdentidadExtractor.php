<?php

declare(strict_types=1);

namespace App\Infrastructure\Document;

use App\Application\Port\DocumentoIdentidadExtractorPort;
use App\Domain\Entity\TipoEscaneoDocumentoIdentidad;

/**
 * Extracción OCR con Tesseract + parser MRZ y campos visibles del reverso DNI/NIE.
 */
final class TesseractDocumentoIdentidadExtractor implements DocumentoIdentidadExtractorPort
{
    public function __construct(
        private DocumentoIdentidadMrzParser $mrzParser,
        private DocumentoIdentidadReversoParser $reversoParser,
        private StubDocumentoIdentidadExtractor $fallback,
    ) {
    }

    public function extract(string $tipoEscaneo, string $anversoPath, ?string $reversoPath): array
    {
        if (!$this->tesseractDisponible()) {
            return $this->fallback->extract($tipoEscaneo, $anversoPath, $reversoPath);
        }

        $tipo = TipoEscaneoDocumentoIdentidad::tryFrom($tipoEscaneo) ?? TipoEscaneoDocumentoIdentidad::DniNie;
        $tipoDocumento = $tipo === TipoEscaneoDocumentoIdentidad::Pasaporte ? 'PASAPORTE' : 'DNI';

        $reversoTexto = null !== $reversoPath && is_file($reversoPath) ? $this->ocr($reversoPath) : '';
        $anversoTexto = is_file($anversoPath) ? $this->ocr($anversoPath) : '';

        $resultado = $this->fallback->vacio($tipoDocumento, false);

        if ('' !== $reversoTexto) {
            $resultado = $this->fusionar($resultado, $this->reversoParser->parseFromText($reversoTexto));
        }

        $mrz = $this->mrzParser->parseFromText($reversoTexto);
        if (null === $mrz && '' !== $anversoTexto) {
            $mrz = $this->mrzParser->parseFromText($anversoTexto);
        }

        if (null !== $mrz) {
            if ($tipo === TipoEscaneoDocumentoIdentidad::Pasaporte) {
                $mrz['tipoDocumento'] = 'PASAPORTE';
            }
            $resultado = $this->fusionar($resultado, $mrz);
            $resultado['extraccionAutomatica'] = true;
        }

        if (!$resultado['extraccionAutomatica']) {
            $heuristica = $this->extraerHeuristico($anversoTexto . "\n" . $reversoTexto, $tipo);
            if ($heuristica['extraccionAutomatica']) {
                $resultado = $this->fusionar($resultado, $heuristica);
            }
        }

        if (
            !$resultado['extraccionAutomatica']
            && '' === $resultado['nombre']
            && '' === $resultado['numDocumento']
            && '' === $resultado['domicilio']
        ) {
            return $this->fallback->extract($tipoEscaneo, $anversoPath, $reversoPath);
        }

        if (
            $resultado['extraccionAutomatica']
            || '' !== $resultado['nombre']
            || '' !== $resultado['numDocumento']
            || '' !== $resultado['domicilio']
            || '' !== $resultado['lugarNacimiento']
        ) {
            $resultado['extraccionAutomatica'] = true;
        }

        return $resultado;
    }

    private function tesseractDisponible(): bool
    {
        $output = [];
        $code = 1;
        exec('tesseract --version 2>/dev/null', $output, $code);

        return 0 === $code;
    }

    private function ocr(string $imagePath): string
    {
        $base = sys_get_temp_dir() . '/ocr-' . bin2hex(random_bytes(8));
        $cmd = sprintf(
            'tesseract %s %s -l spa+eng --oem 1 --psm 6 2>/dev/null',
            escapeshellarg($imagePath),
            escapeshellarg($base),
        );
        exec($cmd);

        $txtPath = $base . '.txt';
        $content = is_file($txtPath) ? (string) file_get_contents($txtPath) : '';
        @unlink($txtPath);

        return $content;
    }

    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $overlay
     *
     * @return array<string, mixed>
     */
    private function fusionar(array $base, array $overlay): array
    {
        foreach ($overlay as $clave => $valor) {
            if ('extraccionAutomatica' === $clave && is_bool($valor)) {
                $base[$clave] = $valor;
                continue;
            }
            if ('fechaNacimiento' === $clave) {
                if (null !== $valor && '' !== $valor) {
                    $base[$clave] = $valor;
                }
                continue;
            }
            if (is_string($valor) && '' !== $valor) {
                $base[$clave] = $valor;
            }
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function extraerHeuristico(string $texto, TipoEscaneoDocumentoIdentidad $tipo): array
    {
        $upper = strtoupper($texto);
        $numDocumento = '';
        $tipoDocumento = $tipo === TipoEscaneoDocumentoIdentidad::Pasaporte ? 'PASAPORTE' : 'DNI';

        if (preg_match('/\b([XYZ]\d{7}[A-Z])\b/', $upper, $m)) {
            $numDocumento = $m[1];
            $tipoDocumento = 'NIE';
        } elseif (preg_match('/\b(\d{8}[A-Z])\b/', $upper, $m)) {
            $numDocumento = $m[1];
            $tipoDocumento = 'DNI';
        }

        return array_merge($this->fallback->vacio($tipoDocumento, '' !== $numDocumento), [
            'numDocumento' => $numDocumento,
            'tipoDocumento' => $tipoDocumento,
            'extraccionAutomatica' => '' !== $numDocumento,
        ]);
    }
}
