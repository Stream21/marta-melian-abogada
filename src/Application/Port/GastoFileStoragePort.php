<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\ValueObject\GastoId;

interface GastoFileStoragePort
{
    public function savePdf(GastoId $gastoId, string $filename, string $content): string;

    public function deleteRelativePath(string $relativePath): void;

    public function deleteFolder(GastoId $gastoId): void;

    public function getAbsolutePath(string $relativePath): string;
}
