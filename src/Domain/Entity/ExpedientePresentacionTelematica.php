<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedientePresentacionTelematicaId;

final readonly class ExpedientePresentacionTelematica
{
    public function __construct(
        private ExpedientePresentacionTelematicaId $id,
        private ExpedienteId $expedienteId,
        private string $presentacionPath,
        private string $justificantePath,
        private string $identificadorSolicitud = '',
        private \DateTimeImmutable $fechaPresentacion = new \DateTimeImmutable('now'),
        private ?string $numeroExpedienteExtranjeria = null,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('now'),
        private \DateTimeImmutable $updatedAt = new \DateTimeImmutable('now'),
    ) {
        if ('' === trim($presentacionPath) || '' === trim($justificantePath)) {
            throw new \InvalidArgumentException('Presentación y justificante son obligatorios.');
        }
    }

    public function id(): ExpedientePresentacionTelematicaId
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function presentacionPath(): string
    {
        return $this->presentacionPath;
    }

    public function justificantePath(): string
    {
        return $this->justificantePath;
    }

    public function identificadorSolicitud(): string
    {
        return $this->identificadorSolicitud;
    }

    public function fechaPresentacion(): \DateTimeImmutable
    {
        return $this->fechaPresentacion;
    }

    public function numeroExpedienteExtranjeria(): ?string
    {
        return $this->numeroExpedienteExtranjeria;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function withSeguimiento(string $numeroExpedienteExtranjeria): self
    {
        $numero = strtoupper(preg_replace('/\s+/', '', trim($numeroExpedienteExtranjeria)) ?? '');
        if (15 !== strlen($numero) || !ctype_alnum($numero)) {
            throw new \InvalidArgumentException(
                'El número de expediente de extranjería debe tener exactamente 15 caracteres alfanuméricos.',
            );
        }

        return new self(
            $this->id,
            $this->expedienteId,
            $this->presentacionPath,
            $this->justificantePath,
            $this->identificadorSolicitud,
            $this->fechaPresentacion,
            $numero,
            $this->createdAt,
            new \DateTimeImmutable('now'),
        );
    }
}
