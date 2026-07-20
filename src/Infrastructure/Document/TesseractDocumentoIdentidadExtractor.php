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

        $reversoTexto = '';
        $reversoMrz = '';
        if (null !== $reversoPath && is_file($reversoPath)) {
            $reversoTexto = $this->ocrGeneral($reversoPath);
            $reversoMrz = $this->ocrBandaMrz($reversoPath);
        }

        $anversoTexto = is_file($anversoPath) ? $this->ocrGeneral($anversoPath) : '';
        $reversoCombinado = trim($reversoTexto . "\n" . $reversoMrz);

        $resultado = $this->fallback->vacio($tipoDocumento, false);

        if ('' !== $reversoTexto) {
            $resultado = $this->fusionar($resultado, $this->reversoParser->parseFromText($reversoTexto));
        }

        $mrz = $this->mrzParser->parseFromText($reversoCombinado);
        if (null === $mrz && '' !== $reversoMrz) {
            $mrz = $this->mrzParser->parseFromText($reversoMrz);
        }

        if (null !== $mrz) {
            if ($tipo === TipoEscaneoDocumentoIdentidad::Pasaporte) {
                $mrz['tipoDocumento'] = 'PASAPORTE';
            }
            $resultado = $this->fusionar($resultado, $mrz);
            $resultado['extraccionAutomatica'] = true;
            $resultado['camposMrz'] = $this->camposDesdeMrz($mrz);
        }

        if ('' === $resultado['nombre'] && '' !== $reversoMrz) {
            $nombreMrz = $this->mrzParser->parseNombreFromText($reversoMrz);
            if ('' !== $nombreMrz) {
                $resultado['nombre'] = $nombreMrz;
                $resultado['camposMrz'] = $this->agregarCampoMrz($resultado['camposMrz'] ?? [], 'nombre');
            }
        }
        if ('' === $resultado['nombre'] && '' !== $reversoCombinado) {
            $nombreMrz = $this->mrzParser->parseNombreFromText($reversoCombinado);
            if ('' !== $nombreMrz) {
                $resultado['nombre'] = $nombreMrz;
                $resultado['camposMrz'] = $this->agregarCampoMrz($resultado['camposMrz'] ?? [], 'nombre');
            }
        }

        if (!$resultado['extraccionAutomatica']) {
            $heuristica = $this->extraerHeuristico($anversoTexto . "\n" . $reversoCombinado, $tipo);
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

    private function ocrGeneral(string $imagePath): string
    {
        $preparada = $this->prepararImagenOcr($imagePath);
        $ruta = $preparada ?? $imagePath;

        $texto = $this->ejecutarTesseract($ruta, [
            'lang' => 'spa+eng',
            'psm' => '6',
        ]);

        if (null !== $preparada) {
            @unlink($preparada);
        }

        return $texto;
    }

  private function ocrBandaMrz(string $imagePath): string
    {
        $lineas = $this->ocrMrzTresLineas($imagePath);
        $textoLineas = trim(implode("\n", array_filter($lineas, static fn (string $l): bool => '' !== trim($l))));
        if ('' !== $textoLineas) {
            return $textoLineas;
        }

        $texto = '';

        $preparada = $this->prepararBandaMrz($imagePath);
        if (null !== $preparada) {
            $texto = $this->ejecutarTesseract($preparada, [
                'lang' => 'eng',
                'psm' => '6',
                'whitelist' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<',
            ]);
            @unlink($preparada);
        }

        if ('' === trim($texto)) {
            $full = $this->prepararImagenMrzCompleta($imagePath);
            $rutaFull = $full ?? $imagePath;
            $texto = $this->ejecutarTesseract($rutaFull, [
                'lang' => 'eng',
                'psm' => '6',
                'whitelist' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<',
            ]);
            if (null !== $full) {
                @unlink($full);
            }
        }

        if ('' === trim($texto)) {
            $prep = $this->prepararImagenOcr($imagePath);
            $ruta = $prep ?? $imagePath;
            $texto = $this->ejecutarTesseract($ruta, [
                'lang' => 'eng',
                'psm' => '4',
                'whitelist' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<',
            ]);
            if (null !== $prep) {
                @unlink($prep);
            }
        }

        return $texto;
    }

    /**
     * OCR línea a línea de la banda MRZ (TD1: 3 líneas). La línea 3 contiene apellidos y nombre.
     *
     * @return list<string>
     */
    private function ocrMrzTresLineas(string $imagePath): array
    {
        $lineas = [];
        for ($i = 0; $i < 3; ++$i) {
            $prep = $this->prepararMrzLinea($imagePath, $i);
            if (null === $prep) {
                continue;
            }
            $linea = trim($this->ejecutarTesseract($prep, [
                'lang' => 'eng',
                'psm' => '7',
                'whitelist' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<',
            ]));
            @unlink($prep);
            if ('' !== $linea) {
                $lineas[] = preg_replace('/\s+/', '', strtoupper($linea)) ?? '';
            }
        }

        return $lineas;
    }

    private function prepararMrzLinea(string $imagePath, int $indiceLinea): ?string
    {
        if (!$this->imagemagickDisponible()) {
            return null;
        }

        $cropAlto = $this->porcentajeBandaMrz($imagePath);
        $offset = (int) floor($indiceLinea * 33.33);
        $out = sys_get_temp_dir() . '/mrzline-' . bin2hex(random_bytes(8)) . '.jpg';
        $cmd = sprintf(
            'convert %s -auto-orient -gravity South -crop 100x%s%%+0+0 +repage -crop 100x34%%+0+%d%% +repage -colorspace Gray -normalize -contrast-stretch 2x2%% -resize 500%% -sharpen 0x1.2 %s 2>/dev/null',
            escapeshellarg($imagePath),
            $cropAlto,
            $offset,
            escapeshellarg($out),
        );
        exec($cmd, $_, $code);

        return 0 === $code && is_file($out) ? $out : null;
    }

    /**
     * @param array{lang: string, psm: string, whitelist?: string} $opciones
     */
    private function ejecutarTesseract(string $imagePath, array $opciones): string
    {
        $base = sys_get_temp_dir() . '/ocr-' . bin2hex(random_bytes(8));
        $config = sprintf('-l %s --oem 1 --psm %s', $opciones['lang'], $opciones['psm']);
        if (isset($opciones['whitelist'])) {
            $config .= sprintf(' -c tessedit_char_whitelist=%s', $opciones['whitelist']);
        }

        $cmd = sprintf(
            'tesseract %s %s %s 2>/dev/null',
            escapeshellarg($imagePath),
            escapeshellarg($base),
            $config,
        );
        exec($cmd);

        $txtPath = $base . '.txt';
        $content = is_file($txtPath) ? (string) file_get_contents($txtPath) : '';
        @unlink($txtPath);

        return $content;
    }

    private function prepararImagenOcr(string $imagePath): ?string
    {
        if (!$this->imagemagickDisponible()) {
            return null;
        }

        $out = sys_get_temp_dir() . '/ocrprep-' . bin2hex(random_bytes(8)) . '.jpg';
        $cmd = sprintf(
            'convert %s -auto-orient -resize "2200x2200>" -colorspace Gray -normalize -sharpen 0x0.8 %s 2>/dev/null',
            escapeshellarg($imagePath),
            escapeshellarg($out),
        );
        exec($cmd, $_, $code);

        return 0 === $code && is_file($out) ? $out : null;
    }

    private function prepararBandaMrz(string $imagePath): ?string
    {
        if (!$this->imagemagickDisponible()) {
            return null;
        }

        $cropAlto = $this->porcentajeBandaMrz($imagePath);
        $out = sys_get_temp_dir() . '/mrz-' . bin2hex(random_bytes(8)) . '.jpg';
        $cmd = sprintf(
            'convert %s -auto-orient -gravity South -crop 100x%s%%+0+0 +repage -colorspace Gray -normalize -contrast-stretch 2x2%% -resize 400%% -sharpen 0x1.2 %s 2>/dev/null',
            escapeshellarg($imagePath),
            $cropAlto,
            escapeshellarg($out),
        );
        exec($cmd, $_, $code);

        return 0 === $code && is_file($out) ? $out : null;
    }

    private function prepararImagenMrzCompleta(string $imagePath): ?string
    {
        if (!$this->imagemagickDisponible()) {
            return null;
        }

        $out = sys_get_temp_dir() . '/mrzfull-' . bin2hex(random_bytes(8)) . '.jpg';
        $cmd = sprintf(
            'convert %s -auto-orient -resize "1600x1600>" -colorspace Gray -normalize -contrast-stretch 1x1%% -sharpen 0x0.8 %s 2>/dev/null',
            escapeshellarg($imagePath),
            escapeshellarg($out),
        );
        exec($cmd, $_, $code);

        return 0 === $code && is_file($out) ? $out : null;
    }

    private function porcentajeBandaMrz(string $imagePath): string
    {
        $info = @getimagesize($imagePath);
        if (false === $info || $info[1] <= 0) {
            return '40';
        }

        $ratio = $info[0] / $info[1];

        // Imagen ya recortada al documento (cámara con marco): MRZ ~30-35% inferior.
        if ($ratio >= 1.25 && $ratio <= 2.1) {
            return '34';
        }

        return '40';
    }

    private function imagemagickDisponible(): bool
    {
        $output = [];
        $code = 1;
        exec('convert -version 2>/dev/null', $output, $code);

        return 0 === $code;
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
            'nombre' => '' !== $numDocumento ? $this->mrzParser->parseNombreFromText($texto) : '',
            'extraccionAutomatica' => '' !== $numDocumento,
            'camposMrz' => '' !== $numDocumento ? ['numDocumento'] : [],
        ]);
    }

    /**
     * @param array<string, mixed> $mrz
     *
     * @return list<string>
     */
    private function camposDesdeMrz(array $mrz): array
    {
        $campos = [];
        foreach (['nombre', 'nacionalidad', 'tipoDocumento', 'numDocumento'] as $campo) {
            if ('' !== trim((string) ($mrz[$campo] ?? ''))) {
                $campos[] = $campo;
            }
        }
        if (null !== ($mrz['fechaNacimiento'] ?? null) && '' !== $mrz['fechaNacimiento']) {
            $campos[] = 'fechaNacimiento';
        }

        return $campos;
    }

    /**
     * @param list<string> $campos
     *
     * @return list<string>
     */
    private function agregarCampoMrz(array $campos, string $campo): array
    {
        if (!in_array($campo, $campos, true)) {
            $campos[] = $campo;
        }

        return $campos;
    }
}
