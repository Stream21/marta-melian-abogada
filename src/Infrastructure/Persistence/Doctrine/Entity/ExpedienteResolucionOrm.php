<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_resolucion')]
#[ORM\UniqueConstraint(name: 'uniq_resolucion_expediente', columns: ['expediente_id'])]
class ExpedienteResolucionOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(name: 'expediente_id', type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $outcome;

    #[ORM\Column(name: 'resolucion_path', type: Types::STRING, length: 500)]
    private string $resolucionPath;

    #[ORM\Column(name: 'fecha_notificacion', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $fechaNotificacion;

    #[ORM\Column(name: 'gestiones_json', type: Types::TEXT)]
    private string $gestionesJson = '[]';

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

    public function getOutcome(): string
    {
        return $this->outcome;
    }

    public function setOutcome(string $outcome): void
    {
        $this->outcome = $outcome;
    }

    public function getResolucionPath(): string
    {
        return $this->resolucionPath;
    }

    public function setResolucionPath(string $resolucionPath): void
    {
        $this->resolucionPath = $resolucionPath;
    }

    public function getFechaNotificacion(): \DateTimeImmutable
    {
        return $this->fechaNotificacion;
    }

    public function setFechaNotificacion(\DateTimeImmutable $fechaNotificacion): void
    {
        $this->fechaNotificacion = $fechaNotificacion;
    }

    public function getGestionesJson(): string
    {
        return $this->gestionesJson;
    }

    public function setGestionesJson(string $gestionesJson): void
    {
        $this->gestionesJson = $gestionesJson;
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
