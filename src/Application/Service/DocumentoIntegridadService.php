<?php

declare(strict_types=1);

namespace App\Application\Service;

final class DocumentoIntegridadService
{
    public function sha256Hex(string $binary): string
    {
        return hash('sha256', $binary);
    }

    public function verificar(string $binary, string $hashEsperado): bool
    {
        $hashEsperado = strtolower(trim($hashEsperado));
        if (64 !== strlen($hashEsperado) || !ctype_xdigit($hashEsperado)) {
            return false;
        }

        return hash_equals($hashEsperado, $this->sha256Hex($binary));
    }
}
