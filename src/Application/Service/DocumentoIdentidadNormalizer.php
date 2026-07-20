<?php

declare(strict_types=1);

namespace App\Application\Service;

final class DocumentoIdentidadNormalizer
{
    public function normalize(?string $numDocumento): string
    {
        if (null === $numDocumento) {
            return '';
        }

        $trimmed = trim($numDocumento);
        if ('' === $trimmed) {
            return '';
        }

        $collapsed = preg_replace('/[\s\-.]/', '', $trimmed) ?? $trimmed;

        return mb_strtoupper($collapsed);
    }
}
