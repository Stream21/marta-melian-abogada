<?php

declare(strict_types=1);

namespace App\Infrastructure\Document;

use App\Application\Port\DocumentoIdentidadExtractorPort;
use App\Domain\Entity\TipoEscaneoDocumentoIdentidad;
use App\Infrastructure\Document\Mrz\MrzResult;
use App\Infrastructure\Document\Mrz\NumeroDocumentoEspanol;

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
        $esPasaporte = $tipo === TipoEscaneoDocumentoIdentidad::Pasaporte;
        $tipoDocumento = $esPasaporte ? 'PASAPORTE' : 'DNI';

        // La MRZ está en el reverso del DNI/NIE, pero en la propia página de datos del pasaporte.
        $paginaMrz = $esPasaporte ? $anversoPath : $reversoPath;
        $lineasMrz = $esPasaporte ? 2 : 3;

        $reversoTexto = '';
        $reversoMrz = '';
        if (null !== $paginaMrz && is_file($paginaMrz)) {
            $reversoTexto = $this->ocrGeneral($paginaMrz);
            $reversoMrz = $this->ocrBandaMrz($paginaMrz, $lineasMrz, $esPasaporte);
        }

        $anversoTexto = !$esPasaporte && is_file($anversoPath) ? $this->ocrGeneral($anversoPath) : $reversoTexto;
        $reversoCombinado = trim($reversoTexto . "\n" . $reversoMrz);

        $resultado = $this->fallback->vacio($tipoDocumento, false);

        if (!$esPasaporte && '' !== $reversoTexto) {
            $resultado = $this->fusionar($resultado, $this->reversoParser->parseFromText($reversoTexto));
        }

        $lectura = $this->mejorLecturaMrz([$reversoMrz, $reversoCombinado, $anversoTexto]);

        // El cliente puede haber invertido las caras: probar la MRZ del otro lado.
        if (null === $lectura && !$esPasaporte && is_file($anversoPath)) {
            $mrzAnverso = $this->ocrBandaMrz($anversoPath, $lineasMrz, false);
            $lectura = $this->mejorLecturaMrz([$mrzAnverso]);
            if (null !== $lectura) {
                $reversoMrz = '' !== $reversoMrz ? $reversoMrz : $mrzAnverso;
                $reversoCombinado = trim($reversoCombinado . "\n" . $mrzAnverso);
            }
        }

        $mrz = null !== $lectura ? $this->mrzParser->mapear($lectura) : null;

        if (null !== $mrz) {
            if ($esPasaporte) {
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

    /**
     * Acumula todas las lecturas de la banda MRZ. El lector puntúa cada hipótesis con
     * los dígitos de control, así que aportar varias variantes mejora el resultado
     * en vez de empeorarlo.
     */
    private function ocrBandaMrz(string $imagePath, int $numLineas, bool $esPasaporte): string
    {
        $textos = [];

        $lineas = $this->ocrMrzLineaALinea($imagePath, $numLineas, $esPasaporte);
        if ([] !== $lineas) {
            $textos[] = implode("\n", $lineas);
        }

        $preparada = $this->prepararBandaMrz($imagePath, $esPasaporte);
        if (null !== $preparada) {
            $textos[] = $this->ejecutarTesseract($preparada, [
                'lang' => 'eng',
                'psm' => '6',
                'whitelist' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<',
            ]);
            @unlink($preparada);
        }

        if ('' === trim(implode('', $textos))) {
            $full = $this->prepararImagenMrzCompleta($imagePath);
            $rutaFull = $full ?? $imagePath;
            $textos[] = $this->ejecutarTesseract($rutaFull, [
                'lang' => 'eng',
                'psm' => '6',
                'whitelist' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<',
            ]);
            if (null !== $full) {
                @unlink($full);
            }

            $prep = $this->prepararImagenOcr($imagePath);
            $ruta = $prep ?? $imagePath;
            $textos[] = $this->ejecutarTesseract($ruta, [
                'lang' => 'eng',
                'psm' => '4',
                'whitelist' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789<',
            ]);
            if (null !== $prep) {
                @unlink($prep);
            }
        }

        return trim(implode("\n", array_filter($textos, static fn (string $t): bool => '' !== trim($t))));
    }

    /**
     * OCR franja a franja de la banda MRZ (TD1: 3 líneas; TD3 de pasaporte: 2).
     * Aislar cada línea evita que Tesseract mezcle caracteres de líneas contiguas.
     *
     * @return list<string>
     */
    private function ocrMrzLineaALinea(string $imagePath, int $numLineas, bool $esPasaporte): array
    {
        $lineas = [];
        for ($i = 0; $i < $numLineas; ++$i) {
            $prep = $this->prepararMrzLinea($imagePath, $i, $numLineas, $esPasaporte);
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

    private function prepararMrzLinea(string $imagePath, int $indiceLinea, int $numLineas, bool $esPasaporte): ?string
    {
        if (!$this->imagemagickDisponible()) {
            return null;
        }

        $cropAlto = $this->porcentajeBandaMrz($imagePath, $esPasaporte);
        // Franjas solapadas: absorben el desencuadre de la foto sin cortar caracteres.
        $altoFranja = (int) round(100 / $numLineas * 1.15);
        $offset = (int) round($indiceLinea * (100 - $altoFranja) / max(1, $numLineas - 1));

        $out = sys_get_temp_dir() . '/mrzline-' . bin2hex(random_bytes(8)) . '.jpg';
        // El segundo recorte usa gravedad North para que el índice 0 sea la primera línea
        // de la MRZ; con South quedarían invertidas y el parser leería los campos cruzados.
        $cmd = sprintf(
            'convert %s -auto-orient -gravity South -crop 100x%s%%+0+0 +repage -gravity North -crop 100x%d%%+0+%d%% +repage -colorspace Gray -normalize -contrast-stretch 2x2%% -resize 500%% -sharpen 0x1.2 %s 2>/dev/null',
            escapeshellarg($imagePath),
            $cropAlto,
            $altoFranja,
            $offset,
            escapeshellarg($out),
        );
        exec($cmd, $_, $code);

        return 0 === $code && is_file($out) ? $out : null;
    }

    /**
     * De todas las lecturas OCR disponibles se queda con la MRZ de mayor confianza
     * (la que valida más dígitos de control).
     *
     * @param list<string> $textos
     */
    private function mejorLecturaMrz(array $textos): ?MrzResult
    {
        $mejor = null;

        foreach ($textos as $texto) {
            if ('' === trim($texto)) {
                continue;
            }
            $lectura = $this->mrzParser->leer($texto);
            if (null !== $lectura && (null === $mejor || $lectura->confianza > $mejor->confianza)) {
                $mejor = $lectura;
            }
        }

        return $mejor;
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

    private function prepararBandaMrz(string $imagePath, bool $esPasaporte = false): ?string
    {
        if (!$this->imagemagickDisponible()) {
            return null;
        }

        $cropAlto = $this->porcentajeBandaMrz($imagePath, $esPasaporte);
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

    private function porcentajeBandaMrz(string $imagePath, bool $esPasaporte = false): string
    {
        $info = @getimagesize($imagePath);
        $ratio = false !== $info && $info[1] > 0 ? $info[0] / $info[1] : 0.0;

        if ($esPasaporte) {
            // Página de datos en vertical (formato libreta): la MRZ ocupa la franja inferior.
            return $ratio > 0 && $ratio < 1.0 ? '22' : '28';
        }

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

        // Se prefiere la primera coincidencia cuya letra de control valide; así una
        // lectura sucia no se cuela como número de documento.
        preg_match_all('/\b([XYZ]\d{7}[A-Z]|\d{8}[A-Z])\b/', $upper, $coincidencias);
        $candidatos = $coincidencias[1] ?? [];

        foreach ($candidatos as $candidato) {
            $corregido = NumeroDocumentoEspanol::corregir($candidato);
            if (null !== $corregido && NumeroDocumentoEspanol::esValido($corregido)) {
                $numDocumento = $corregido;
                break;
            }
        }

        if ('' === $numDocumento && [] !== $candidatos) {
            $numDocumento = $candidatos[0];
        }

        if ('' !== $numDocumento && $tipo !== TipoEscaneoDocumentoIdentidad::Pasaporte) {
            $tipoDocumento = 1 === preg_match('/^[XYZ]/', $numDocumento) ? 'NIE' : 'DNI';
        } elseif ($tipo === TipoEscaneoDocumentoIdentidad::Pasaporte) {
            // En un pasaporte un patrón tipo DNI en el texto visible no es el nº de documento.
            $numDocumento = '';
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
