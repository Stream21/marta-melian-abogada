<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\ValueObject\ClienteId;

interface ClienteFileStoragePort
{
    public function getFolderPath(ClienteId $clienteId): string;

    public function saveDocumentoIdentidad(ClienteId $clienteId, string $filename, string $content): string;

    public function resolveAbsolutePath(string $relativePath): string;
}
