<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class GuardarDocumentosRequeridosInput
{
    /**
     * @param list<array<string, mixed>> $documentos
     */
    public function __construct(
        public string $tramiteId,
        public array $documentos,
    ) {
    }
}
