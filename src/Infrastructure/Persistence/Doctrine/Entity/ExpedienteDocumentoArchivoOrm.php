<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_documento_archivo')]
class ExpedienteDocumentoArchivoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $entregadoId;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $archivoPath;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $nombreOriginal;

    #[ORM\Column(type: Types::INTEGER)]
    private int $orden = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getEntregadoId(): string
    {
        return $this->entregadoId;
    }

    public function setEntregadoId(string $entregadoId): void
    {
        $this->entregadoId = $entregadoId;
    }

    public function getArchivoPath(): string
    {
        return $this->archivoPath;
    }

    public function setArchivoPath(string $archivoPath): void
    {
        $this->archivoPath = $archivoPath;
    }

    public function getNombreOriginal(): string
    {
        return $this->nombreOriginal;
    }

    public function setNombreOriginal(string $nombreOriginal): void
    {
        $this->nombreOriginal = $nombreOriginal;
    }

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): void
    {
        $this->orden = $orden;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
