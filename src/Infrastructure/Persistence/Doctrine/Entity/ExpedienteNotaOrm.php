<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'expediente_nota')]
#[ORM\Index(name: 'idx_nota_expediente_created', columns: ['expediente_id', 'created_at'])]
class ExpedienteNotaOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(name: 'expediente_id', type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::TEXT)]
    private string $contenido;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $archivada = false;

    #[ORM\Column(name: 'archivada_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $archivadaAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
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

    public function getContenido(): string
    {
        return $this->contenido;
    }

    public function setContenido(string $contenido): void
    {
        $this->contenido = $contenido;
    }

    public function isArchivada(): bool
    {
        return $this->archivada;
    }

    public function setArchivada(bool $archivada): void
    {
        $this->archivada = $archivada;
    }

    public function getArchivadaAt(): ?\DateTimeImmutable
    {
        return $this->archivadaAt;
    }

    public function setArchivadaAt(?\DateTimeImmutable $archivadaAt): void
    {
        $this->archivadaAt = $archivadaAt;
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
