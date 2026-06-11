<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Port\ClienteFileStoragePort;
use App\Domain\ValueObject\ClienteId;

final class ClienteFileStorage implements ClienteFileStoragePort
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function getFolderPath(ClienteId $clienteId): string
    {
        return $this->projectDir . '/var/clientes/' . $clienteId->value() . '/';
    }

    public function saveDocumentoIdentidad(ClienteId $clienteId, string $filename, string $content): string
    {
        $folder = $this->getFolderPath($clienteId) . 'documento-identidad/';
        $this->ensureWritableDirectory($folder);

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) ?? 'documento.jpg';
        $path = $folder . $safeName;

        if (false === file_put_contents($path, $content)) {
            throw new \RuntimeException('No se pudo guardar el documento de identidad en el servidor.');
        }

        return 'var/clientes/' . $clienteId->value() . '/documento-identidad/' . $safeName;
    }

    public function resolveAbsolutePath(string $relativePath): string
    {
        return $this->projectDir . '/' . ltrim($relativePath, '/');
    }

    private function ensureWritableDirectory(string $folder): void
    {
        if (is_dir($folder) && is_writable($folder)) {
            return;
        }

        if (!is_dir($folder) && !@mkdir($folder, 0775, true) && !is_dir($folder)) {
            throw new \RuntimeException(
                'No se pudo crear el directorio de documentos del cliente (var/clientes). '
                . 'Reinicie el contenedor php o ejecute: docker-compose exec -u root php '
                . 'mkdir -p /app/var/clientes && chown -R www-data:www-data /app/var/clientes',
            );
        }

        if (!is_writable($folder)) {
            throw new \RuntimeException(
                'El directorio var/clientes no tiene permisos de escritura para el servidor.',
            );
        }
    }
}
