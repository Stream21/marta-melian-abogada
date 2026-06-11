<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_contratacion_paso')]
#[ORM\UniqueConstraint(name: 'UNIQ_CONTRATACION_EXPEDIENTE_PASO', columns: ['expediente_id', 'paso'])]
class ContratacionPasoOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $paso;

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $estado = 'pendiente';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $realizadoAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validadoAt = null;

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

    public function getPaso(): string
    {
        return $this->paso;
    }

    public function setPaso(string $paso): void
    {
        $this->paso = $paso;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }

    public function getRealizadoAt(): ?\DateTimeImmutable
    {
        return $this->realizadoAt;
    }

    public function setRealizadoAt(?\DateTimeImmutable $realizadoAt): void
    {
        $this->realizadoAt = $realizadoAt;
    }

    public function getValidadoAt(): ?\DateTimeImmutable
    {
        return $this->validadoAt;
    }

    public function setValidadoAt(?\DateTimeImmutable $validadoAt): void
    {
        $this->validadoAt = $validadoAt;
    }
}
