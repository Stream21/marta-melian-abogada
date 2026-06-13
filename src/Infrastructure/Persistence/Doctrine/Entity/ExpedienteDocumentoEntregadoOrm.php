<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_documento_entregado')]
#[ORM\UniqueConstraint(name: 'UNIQ_EXP_DOC_ENTREGADO', columns: ['expediente_id', 'documento_requerido_id'])]
class ExpedienteDocumentoEntregadoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $documentoRequeridoId;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $archivoPath;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $estado = 'entregado';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $entregadoAt;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getExpedienteId(): string
    {
        return $this->expedienteId;
    }

    public function setExpedienteId(string $expedienteId): void
    {
        $this->expedienteId = $expedienteId;
    }

    public function getDocumentoRequeridoId(): string
    {
        return $this->documentoRequeridoId;
    }

    public function setDocumentoRequeridoId(string $documentoRequeridoId): void
    {
        $this->documentoRequeridoId = $documentoRequeridoId;
    }

    public function getArchivoPath(): string
    {
        return $this->archivoPath;
    }

    public function setArchivoPath(string $archivoPath): void
    {
        $this->archivoPath = $archivoPath;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }

    public function getEntregadoAt(): \DateTimeImmutable
    {
        return $this->entregadoAt;
    }

    public function setEntregadoAt(\DateTimeImmutable $entregadoAt): void
    {
        $this->entregadoAt = $entregadoAt;
    }
}
