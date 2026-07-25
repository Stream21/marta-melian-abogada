<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_presentacion_telematica')]
#[ORM\UniqueConstraint(name: 'uniq_presentacion_expediente', columns: ['expediente_id'])]
class ExpedientePresentacionTelematicaOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(name: 'expediente_id', type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $presentacionPath;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $justificantePath;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $identificadorSolicitud;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $fechaPresentacion;

    #[ORM\Column(type: Types::STRING, length: 15, nullable: true)]
    private ?string $numeroExpedienteExtranjeria = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

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

    public function getPresentacionPath(): string
    {
        return $this->presentacionPath;
    }

    public function setPresentacionPath(string $presentacionPath): void
    {
        $this->presentacionPath = $presentacionPath;
    }

    public function getJustificantePath(): string
    {
        return $this->justificantePath;
    }

    public function setJustificantePath(string $justificantePath): void
    {
        $this->justificantePath = $justificantePath;
    }

    public function getIdentificadorSolicitud(): string
    {
        return $this->identificadorSolicitud;
    }

    public function setIdentificadorSolicitud(string $identificadorSolicitud): void
    {
        $this->identificadorSolicitud = $identificadorSolicitud;
    }

    public function getFechaPresentacion(): \DateTimeImmutable
    {
        return $this->fechaPresentacion;
    }

    public function setFechaPresentacion(\DateTimeImmutable $fechaPresentacion): void
    {
        $this->fechaPresentacion = $fechaPresentacion;
    }

    public function getNumeroExpedienteExtranjeria(): ?string
    {
        return $this->numeroExpedienteExtranjeria;
    }

    public function setNumeroExpedienteExtranjeria(?string $numeroExpedienteExtranjeria): void
    {
        $this->numeroExpedienteExtranjeria = $numeroExpedienteExtranjeria;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
