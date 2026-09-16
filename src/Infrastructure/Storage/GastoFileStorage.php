<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Port\GastoFileStoragePort;
use App\Domain\ValueObject\GastoId;

final class GastoFileStorage implements GastoFileStoragePort
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function savePdf(GastoId $gastoId, string $filename, string $content): string
    {
        $folder = $this->projectDir . '/var/gastos/' . $gastoId->value() . '/';
        if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
            throw new \RuntimeException('No se pudo crear el directorio de gastos.');
        }

        $path = $folder . $filename;
        if (false === file_put_contents($path, $content)) {
            throw new \RuntimeException('No se pudo guardar la factura del gasto.');
        }

        return 'var/gastos/' . $gastoId->value() . '/' . $filename;
    }

    public function deleteRelativePath(string $relativePath): void
    {
        $absolute = $this->getAbsolutePath($relativePath);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    public function deleteFolder(GastoId $gastoId): void
    {
        $folder = $this->projectDir . '/var/gastos/' . $gastoId->value();
        if (!is_dir($folder)) {
            return;
        }

        $files = scandir($folder);
        if (false === $files) {
            return;
        }

        foreach ($files as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            $path = $folder . '/' . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        @rmdir($folder);
    }

    public function getAbsolutePath(string $relativePath): string
    {
        return $this->projectDir . '/' . ltrim($relativePath, '/');
    }
}
