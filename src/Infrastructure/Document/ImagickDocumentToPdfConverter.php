<?php

declare(strict_types=1);

namespace App\Infrastructure\Document;

use App\Application\Port\DocumentToPdfConverterPort;
use App\Infrastructure\Http\Utf8Sanitizer;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Convierte imágenes, PDF y documentos Office a PDF y combina varias fuentes en un único PDF.
 * Usa Imagick si está disponible; si no, Dompdf como respaldo para imágenes.
 */
final class ImagickDocumentToPdfConverter implements DocumentToPdfConverterPort
{
    private const IMAGENES_PERMITIDAS = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    private const OFFICE_MIMES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
        'application/rtf',
        'text/rtf',
    ];

    public function __construct(
        private string $projectDir,
    ) {
    }

    public function convertToPdf(string $sourceAbsolutePath, string $mimeType): string
    {
        if (!is_file($sourceAbsolutePath)) {
            throw new \InvalidArgumentException('Archivo de origen no encontrado.');
        }

        $absolutePdf = $this->convertSourceToPdfAbsolute($sourceAbsolutePath, $mimeType);

        try {
            return $this->storeCopy($absolutePdf, 'pdf');
        } finally {
            if ($absolutePdf !== $sourceAbsolutePath) {
                @unlink($absolutePdf);
            }
        }
    }

    public function mergeSourcesToPdf(array $sources): string
    {
        if ([] === $sources) {
            throw new \InvalidArgumentException('No hay archivos para combinar.');
        }

        if (1 === count($sources)) {
            return $this->convertToPdf($sources[0]['path'], $sources[0]['mime']);
        }

        if ($this->allSourcesAreImages($sources)) {
            return $this->mergeImagenesToPdf($sources);
        }

        $temporales = [];
        try {
            $pdfPaths = [];
            foreach ($sources as $source) {
                $pdf = $this->convertSourceToPdfAbsolute($source['path'], $source['mime']);
                $pdfPaths[] = $pdf;
                if ($pdf !== $source['path']) {
                    $temporales[] = $pdf;
                }
            }

            $merged = $this->combinarPdfsAbsolutos($pdfPaths);
            $temporales[] = $merged;

            return $this->storeCopy($merged, 'pdf');
        } finally {
            foreach ($temporales as $temp) {
                @unlink($temp);
            }
        }
    }

    /**
     * @param list<array{path: string, mime: string}> $sources
     */
    private function mergeImagenesToPdf(array $sources): string
    {
        $temporales = [];

        try {
            $jpegs = [];
            foreach ($sources as $source) {
                $jpeg = $this->normalizarImagenAJpeg($source['path']);
                $jpegs[] = $jpeg;
                if ($jpeg !== $source['path']) {
                    $temporales[] = $jpeg;
                }
            }

            $binary = $this->imagemagickBinary();
            if (null !== $binary) {
                try {
                    $merged = $this->mergeJpegsToPdfConConvert($jpegs, $binary);
                    $temporales[] = $merged;

                    return $this->storeCopy($merged, 'pdf');
                } catch (\Throwable) {
                    // Imagick o merge vía PDFs individuales.
                }
            }

            if (extension_loaded('imagick')) {
                try {
                    $merged = $this->convertirImagenesConImagick($jpegs);
                    $temporales[] = $merged;

                    return $this->storeCopy($merged, 'pdf');
                } catch (\Throwable) {
                    // Ghostscript página a página o Dompdf.
                }
            }

            if ($this->commandExists('gs')) {
                try {
                    $merged = $this->mergeJpegsToPdfViaPdfsIndividuales($jpegs, $temporales);
                    $temporales[] = $merged;

                    return $this->storeCopy($merged, 'pdf');
                } catch (\Throwable) {
                    // Dompdf como último recurso.
                }
            }

            $dompdfSources = array_map(
                static fn (string $path): array => ['path' => $path, 'mime' => 'image/jpeg'],
                $jpegs,
            );
            $merged = $this->convertirImagenesConDompdf($dompdfSources);
            $temporales[] = $merged;

            return $this->storeCopy($merged, 'pdf');
        } finally {
            foreach ($temporales as $temp) {
                @unlink($temp);
            }
        }
    }

    /**
     * @param list<array{path: string, mime: string}> $sources
     */
    private function allSourcesAreImages(array $sources): bool
    {
        foreach ($sources as $source) {
            $mime = strtolower(trim($source['mime']));
            if (!in_array($mime, self::IMAGENES_PERMITIDAS, true)) {
                return false;
            }
        }

        return true;
    }

    private function convertSourceToPdfAbsolute(string $sourceAbsolutePath, string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));

        if ('application/pdf' === $mimeType || $this->esPdf($sourceAbsolutePath)) {
            return $sourceAbsolutePath;
        }

        if (in_array($mimeType, self::IMAGENES_PERMITIDAS, true)) {
            return $this->convertirImagenAPdfAbsolute($sourceAbsolutePath);
        }

        if ($this->esOffice($mimeType, $sourceAbsolutePath)) {
            return $this->convertirOfficeAPdfAbsolute($sourceAbsolutePath);
        }

        throw new \InvalidArgumentException(
            sprintf('Tipo de archivo no convertible a PDF: %s', $mimeType),
        );
    }

    private function convertirImagenAPdfAbsolute(string $sourceAbsolutePath): string
    {
        $temporales = [];

        try {
            $jpeg = $this->normalizarImagenAJpeg($sourceAbsolutePath);
            if ($jpeg !== $sourceAbsolutePath) {
                $temporales[] = $jpeg;
            }

            $binary = $this->imagemagickBinary();
            if (null !== $binary) {
                try {
                    return $this->mergeJpegsToPdfConConvert([$jpeg], $binary);
                } catch (\Throwable) {
                    // Ghostscript o extensión Imagick.
                }
            }

            if ($this->commandExists('gs')) {
                try {
                    return $this->convertirJpegAPdfConGhostscript($jpeg);
                } catch (\Throwable) {
                    // Imagick como respaldo si Ghostscript falla con este JPEG.
                }
            }

            if (extension_loaded('imagick')) {
                return $this->convertirImagenesConImagick([$jpeg]);
            }

            return $this->convertirImagenesConDompdf([['path' => $jpeg, 'mime' => 'image/jpeg']]);
        } finally {
            foreach ($temporales as $temp) {
                @unlink($temp);
            }
        }
    }

    /**
     * Comprime y escala cualquier imagen a JPEG optimizado para PDF.
     * Prioridad: CLI ImageMagick (rápido, bajo consumo) → extensión Imagick → GD.
     */
    private function normalizarImagenAJpeg(string $sourceAbsolutePath): string
    {
        $binary = $this->imagemagickBinary();
        if (null !== $binary) {
            try {
                return $this->normalizarImagenAJpegConConvert($sourceAbsolutePath, $binary);
            } catch (\Throwable) {
                // CLI falló (p. ej. policy, formato); probar otros backends.
            }
        }

        if (extension_loaded('imagick')) {
            try {
                return $this->normalizarImagenAJpegConImagick($sourceAbsolutePath);
            } catch (\Throwable) {
                // Último recurso: GD.
            }
        }

        if (extension_loaded('gd')) {
            return $this->normalizarImagenAJpegConGd($sourceAbsolutePath);
        }

        throw new \RuntimeException('No hay conversor de imágenes disponible (ImageMagick, GD o Imagick).');
    }

    private function normalizarImagenAJpegConConvert(string $sourceAbsolutePath, string $binary): string
    {
        $jpegPath = $this->nuevaRutaTemporal('jpg');
        // El ">" del resize debe ir entrecomillado: sin eso el shell lo interpreta como redirección.
        $parts = [
            escapeshellarg($sourceAbsolutePath),
            '-auto-orient',
            '-background',
            'white',
            '-alpha',
            'remove',
            '-flatten',
            '-resize',
            escapeshellarg('1800x1800>'),
            '-strip',
            '-quality',
            '82',
            escapeshellarg($jpegPath),
        ];
        $cmd = ('magick' === $binary ? 'magick ' : 'convert ') . implode(' ', $parts) . ' 2>&1';

        exec($cmd, $output, $code);

        if (0 !== $code || !is_file($jpegPath) || filesize($jpegPath) === 0) {
            @unlink($jpegPath);
            throw new \RuntimeException($this->mensajeFalloImagen('ImageMagick no pudo preparar la imagen.', $output));
        }

        return $jpegPath;
    }

    private function normalizarImagenAJpegConGd(string $sourceAbsolutePath): string
    {
        $info = @getimagesize($sourceAbsolutePath);
        if (false === $info) {
            throw new \InvalidArgumentException('No se pudo leer la imagen.');
        }

        $mime = strtolower((string) ($info['mime'] ?? ''));
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourceAbsolutePath),
            'image/png' => @imagecreatefrompng($sourceAbsolutePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourceAbsolutePath) : false,
            'image/gif' => @imagecreatefromgif($sourceAbsolutePath),
            default => false,
        };

        if (false === $source) {
            throw new \InvalidArgumentException('Formato de imagen no soportado.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxSide = 1800;
        $maxPixels = 4_000_000;
        $needsResize = $width > $maxSide || $height > $maxSide || ($width * $height) > $maxPixels;

        if ($needsResize) {
            $ratio = min($maxSide / $width, $maxSide / $height, 1.0);
            $targetWidth = max(1, (int) round($width * $ratio));
            $targetHeight = max(1, (int) round($height * $ratio));
            $resized = imagecreatetruecolor($targetWidth, $targetHeight);
            if (false === $resized) {
                imagedestroy($source);
                throw new \RuntimeException('No se pudo preparar la imagen.');
            }

            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefill($resized, 0, 0, $white);
            imagecopyresampled(
                $resized,
                $source,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $width,
                $height,
            );
            imagedestroy($source);
            $source = $resized;
        } elseif ('image/png' === $mime || 'image/gif' === $mime) {
            $flattened = imagecreatetruecolor($width, $height);
            if (false === $flattened) {
                imagedestroy($source);
                throw new \RuntimeException('No se pudo preparar la imagen.');
            }
            $white = imagecolorallocate($flattened, 255, 255, 255);
            imagefill($flattened, 0, 0, $white);
            imagecopy($flattened, $source, 0, 0, 0, 0, $width, $height);
            imagedestroy($source);
            $source = $flattened;
        }

        $jpegPath = $this->nuevaRutaTemporal('jpg');
        if (!imagejpeg($source, $jpegPath, 82)) {
            imagedestroy($source);
            throw new \RuntimeException('No se pudo comprimir la imagen.');
        }

        imagedestroy($source);

        return $jpegPath;
    }

    private function normalizarImagenAJpegConImagick(string $sourceAbsolutePath): string
    {
        $imagick = new \Imagick();
        $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 512 * 1024 * 1024);
        $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MAP, 512 * 1024 * 1024);

        try {
            $imagick->readImage($sourceAbsolutePath);
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();
            $maxSide = 1800;

            if ($width > $maxSide || $height > $maxSide) {
                $imagick->resizeImage($maxSide, $maxSide, \Imagick::FILTER_LANCZOS, 1, true);
            }

            $imagick->setImageBackgroundColor('white');
            $imagick->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
            $flattened = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            if ($flattened instanceof \Imagick) {
                $imagick->clear();
                $imagick->destroy();
                $imagick = $flattened;
            }
            $imagick->setImageFormat('jpeg');
            $imagick->setImageCompressionQuality(82);
            $jpegPath = $this->nuevaRutaTemporal('jpg');
            $imagick->writeImage($jpegPath);

            return $jpegPath;
        } finally {
            $imagick->clear();
            $imagick->destroy();
        }
    }

    /**
     * @param list<string> $jpegPaths
     */
    private function mergeJpegsToPdfConConvert(array $jpegPaths, string $binary): string
    {
        $output = $this->nuevaRutaTemporal('pdf');
        $inputs = implode(' ', array_map('escapeshellarg', $jpegPaths));

        if ('magick' === $binary) {
            $cmd = sprintf('magick %s %s 2>&1', $inputs, escapeshellarg($output));
        } else {
            $cmd = sprintf('convert %s %s 2>&1', $inputs, escapeshellarg($output));
        }

        exec($cmd, $convertOutput, $code);

        if (0 !== $code || !is_file($output) || filesize($output) === 0) {
            @unlink($output);
            throw new \RuntimeException(
                $this->mensajeFalloImagen('ImageMagick no pudo combinar las imágenes en PDF.', $convertOutput),
            );
        }

        return $output;
    }

    /**
     * Ghostscript no admite varios JPEG en una sola pasada; convierte cada uno a PDF y luego une.
     *
     * @param list<string> $jpegPaths
     * @param list<string> $temporales
     */
    private function mergeJpegsToPdfViaPdfsIndividuales(array $jpegPaths, array &$temporales): string
    {
        $pdfPaths = [];
        foreach ($jpegPaths as $jpegPath) {
            $pdf = $this->convertirJpegAPdfConGhostscript($jpegPath);
            $pdfPaths[] = $pdf;
            $temporales[] = $pdf;
        }

        return $this->combinarPdfsAbsolutos($pdfPaths);
    }

    private function convertirJpegAPdfConGhostscript(string $jpegPath): string
    {
        $output = $this->nuevaRutaTemporal('pdf');
        $cmd = sprintf(
            'gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -dPDFSETTINGS=/ebook -sOutputFile=%s %s 2>&1',
            escapeshellarg($output),
            escapeshellarg($jpegPath),
        );
        exec($cmd, $gsOutput, $code);

        if (0 !== $code || !is_file($output) || filesize($output) === 0) {
            @unlink($output);
            throw new \RuntimeException(
                $this->mensajeFalloImagen('Ghostscript no pudo convertir la imagen a PDF.', $gsOutput),
            );
        }

        return $output;
    }

    /**
     * @param list<string> $paths
     */
    private function convertirImagenesConImagick(array $paths): string
    {
        $imagick = new \Imagick();
        $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 512 * 1024 * 1024);
        $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MAP, 512 * 1024 * 1024);
        foreach ($paths as $path) {
            $imagick->readImage($path);
        }
        $imagick->setImageFormat('pdf');

        $absolute = $this->nuevaRutaTemporal('pdf');
        $imagick->writeImages($absolute, true);
        $imagick->clear();
        $imagick->destroy();

        return $absolute;
    }

    /**
     * @param list<array{path: string, mime: string}> $sources
     */
    private function convertirImagenesConDompdf(array $sources): string
    {
        $paginas = '';
        foreach ($sources as $source) {
            $mime = htmlspecialchars(strtolower(trim($source['mime'])), ENT_QUOTES, 'UTF-8');
            $data = base64_encode((string) file_get_contents($source['path']));
            $paginas .= sprintf(
                '<div style="page-break-after: always; text-align: center; margin: 0; padding: 0;">'
                . '<img src="data:%s;base64,%s" style="max-width: 100%%; height: auto;" />'
                . '</div>',
                $mime,
                $data,
            );
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $paginas . '</body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'dejavu sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $absolute = $this->nuevaRutaTemporal('pdf');
        file_put_contents($absolute, (string) $dompdf->output());

        return $absolute;
    }

    /**
     * @param list<string> $pdfPaths
     */
    private function combinarPdfsAbsolutos(array $pdfPaths): string
    {
        $output = $this->nuevaRutaTemporal('pdf');

        if ($this->commandExists('gs')) {
            $cmd = sprintf(
                'gs -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s 2>&1',
                escapeshellarg($output),
                implode(' ', array_map('escapeshellarg', $pdfPaths)),
            );
            exec($cmd, $gsOutput, $code);
            if (0 === $code && is_file($output) && filesize($output) > 0) {
                return $output;
            }
        }

        if (extension_loaded('imagick')) {
            return $this->combinarPdfsConImagick($pdfPaths, $output);
        }

        throw new \RuntimeException('No se pudo combinar los archivos en un único PDF.');
    }

    /**
     * @param list<string> $pdfPaths
     */
    private function combinarPdfsConImagick(array $pdfPaths, string $output): string
    {
        $imagick = new \Imagick();
        foreach ($pdfPaths as $path) {
            $imagick->readImage($path);
        }
        $imagick->setImageFormat('pdf');
        $imagick->writeImages($output, true);
        $imagick->clear();
        $imagick->destroy();

        if (!is_file($output) || filesize($output) === 0) {
            throw new \RuntimeException('No se pudo combinar los archivos en un único PDF.');
        }

        return $output;
    }

    private function convertirOfficeAPdfAbsolute(string $sourceAbsolutePath): string
    {
        $outDir = sys_get_temp_dir() . '/lo-out-' . bin2hex(random_bytes(8));
        if (!mkdir($outDir, 0755, true) && !is_dir($outDir)) {
            throw new \RuntimeException('No se pudo preparar la conversión del documento Office.');
        }

        try {
            foreach (['libreoffice', 'soffice'] as $binary) {
                if (!$this->commandExists($binary)) {
                    continue;
                }

                $cmd = sprintf(
                    '%s --headless --convert-to pdf --outdir %s %s 2>&1',
                    escapeshellcmd($binary),
                    escapeshellarg($outDir),
                    escapeshellarg($sourceAbsolutePath),
                );
                exec($cmd, $output, $code);

                if (0 !== $code) {
                    continue;
                }

                $pdfs = glob($outDir . '/*.pdf') ?: [];
                if ([] === $pdfs) {
                    continue;
                }

                $result = $this->nuevaRutaTemporal('pdf');
                if (!rename($pdfs[0], $result)) {
                    throw new \RuntimeException('No se pudo guardar el PDF convertido.');
                }

                return $result;
            }

            throw new \InvalidArgumentException(
                'No se pudo convertir el documento Office a PDF. Compruebe que el formato sea compatible.',
            );
        } finally {
            $this->removeDir($outDir);
        }
    }

    private function esOffice(string $mimeType, string $path): bool
    {
        if (in_array($mimeType, self::OFFICE_MIMES, true)) {
            return true;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, ['doc', 'docx', 'odt', 'rtf'], true);
    }

    private function imagemagickBinary(): ?string
    {
        if ($this->commandExists('magick')) {
            return 'magick';
        }

        if ($this->commandExists('convert')) {
            return 'convert';
        }

        return null;
    }

    /**
     * @param list<string> $output
     */
    private function mensajeFalloImagen(string $base, array $output): string
    {
        $detail = Utf8Sanitizer::sanitizeCommandOutput($output);
        if ('' === $detail) {
            return $base;
        }

        return $base . ' ' . $detail;
    }

    private function commandExists(string $command): bool
    {
        $cmd = sprintf('command -v %s 2>/dev/null', escapeshellcmd($command));
        exec($cmd, $output, $code);

        return 0 === $code && [] !== $output;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($dir);
    }

    private function storeCopy(string $sourceAbsolutePath, string $extension): string
    {
        $absolute = $this->nuevaRutaAbsoluta($extension);
        copy($sourceAbsolutePath, $absolute);

        return $this->rutaRelativa($absolute);
    }

    private function nuevaRutaAbsoluta(string $extension = 'pdf'): string
    {
        $folder = $this->projectDir . '/var/documentos/convertidos/';
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        return $folder . bin2hex(random_bytes(16)) . '.' . $extension;
    }

    private function nuevaRutaTemporal(string $extension = 'pdf'): string
    {
        return sys_get_temp_dir() . '/doc-pdf-' . bin2hex(random_bytes(16)) . '.' . $extension;
    }

    private function rutaRelativa(string $absolute): string
    {
        $relative = str_replace('\\', '/', substr($absolute, strlen($this->projectDir) + 1));

        return ltrim($relative, '/');
    }

    private function esPdf(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if (false === $handle) {
            return false;
        }

        $header = fread($handle, 4);
        fclose($handle);

        return '%PDF' === $header;
    }
}
