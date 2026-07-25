<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_recordatorio_futuro')]
#[ORM\UniqueConstraint(name: 'uniq_recordatorio_expediente', columns: ['expediente_id'])]
class ExpedienteRecordatorioFuturoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(name: 'expediente_id', type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $fecha;

    #[ORM\Column(type: Types::TEXT)]
    private string $motivo;

    #[ORM\Column(name: 'servicio_id', type: Types::STRING, length: 36, nullable: true)]
    private ?string $servicioId = null;

    #[ORM\Column(name: 'tramite_id', type: Types::STRING, length: 36, nullable: true)]
    private ?string $tramiteId = null;

    #[ORM\Column(name: 'servicio_nombre', type: Types::STRING, length: 255, nullable: true)]
    private ?string $servicioNombre = null;

    #[ORM\Column(name: 'tramite_nombre', type: Types::STRING, length: 255, nullable: true)]
    private ?string $tramiteNombre = null;

    #[ORM\Column(name: 'notificado_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $notificadoAt = null;

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

    public function getExpedienteId(): string
    {
        return $this->expedienteId;
    }

    public function setExpedienteId(string $expedienteId): void
    {
        $this->expedienteId = $expedienteId;
    }

    public function getFecha(): \DateTimeImmutable
    {
        return $this->fecha;
    }

    public function setFecha(\DateTimeImmutable $fecha): void
    {
        $this->fecha = $fecha;
    }

    public function getMotivo(): string
    {
        return $this->motivo;
    }

    public function setMotivo(string $motivo): void
    {
        $this->motivo = $motivo;
    }

    public function getServicioId(): ?string
    {
        return $this->servicioId;
    }

    public function setServicioId(?string $servicioId): void
    {
        $this->servicioId = $servicioId;
    }

    public function getTramiteId(): ?string
    {
        return $this->tramiteId;
    }

    public function setTramiteId(?string $tramiteId): void
    {
        $this->tramiteId = $tramiteId;
    }

    public function getServicioNombre(): ?string
    {
        return $this->servicioNombre;
    }

    public function setServicioNombre(?string $servicioNombre): void
    {
        $this->servicioNombre = $servicioNombre;
    }

    public function getTramiteNombre(): ?string
    {
        return $this->tramiteNombre;
    }

    public function setTramiteNombre(?string $tramiteNombre): void
    {
        $this->tramiteNombre = $tramiteNombre;
    }

    public function getNotificadoAt(): ?\DateTimeImmutable
    {
        return $this->notificadoAt;
    }

    public function setNotificadoAt(?\DateTimeImmutable $notificadoAt): void
    {
        $this->notificadoAt = $notificadoAt;
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
