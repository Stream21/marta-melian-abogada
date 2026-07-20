<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_documento_entregado')]
class ExpedienteDocumentoEntregadoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::STRING, length: 36, nullable: true)]
    private ?string $documentoRequeridoId = null;

    #[ORM\Column(type: Types::STRING, length: 36, nullable: true)]
    private ?string $expedienteDocumentoRequeridoId = null;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $archivoPath;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $estado = 'entregado';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $entregadoAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notaRechazo = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $subidoPor = 'cliente';

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $responsableActual = 'cliente';

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

    public function getDocumentoRequeridoId(): ?string
    {
        return $this->documentoRequeridoId;
    }

    public function setDocumentoRequeridoId(?string $documentoRequeridoId): void
    {
        $this->documentoRequeridoId = $documentoRequeridoId;
    }

    public function getExpedienteDocumentoRequeridoId(): ?string
    {
        return $this->expedienteDocumentoRequeridoId;
    }

    public function setExpedienteDocumentoRequeridoId(?string $expedienteDocumentoRequeridoId): void
    {
        $this->expedienteDocumentoRequeridoId = $expedienteDocumentoRequeridoId;
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

    public function getNotaRechazo(): ?string
    {
        return $this->notaRechazo;
    }

    public function setNotaRechazo(?string $notaRechazo): void
    {
        $this->notaRechazo = $notaRechazo;
    }

    public function getSubidoPor(): string
    {
        return $this->subidoPor;
    }

    public function setSubidoPor(string $subidoPor): void
    {
        $this->subidoPor = $subidoPor;
    }

    public function getResponsableActual(): string
    {
        return $this->responsableActual;
    }

    public function setResponsableActual(string $responsableActual): void
    {
        $this->responsableActual = $responsableActual;
    }
}
