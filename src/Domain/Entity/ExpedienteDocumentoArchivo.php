<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final readonly class ExpedienteDocumentoArchivo
{
    public function __construct(
        private string $id,
        private string $entregadoId,
        private string $archivoPath,
        private string $nombreOriginal,
        private int $orden,
        private \DateTimeImmutable $createdAt,
    ) {
        if ('' === trim($this->archivoPath)) {
            throw new \InvalidArgumentException('La ruta del archivo es obligatoria.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function entregadoId(): string
    {
        return $this->entregadoId;
    }

    public function archivoPath(): string
    {
        return $this->archivoPath;
    }

    public function nombreOriginal(): string
    {
        return $this->nombreOriginal;
    }

    public function orden(): int
    {
        return $this->orden;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
