<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;

final readonly class Expediente
{
    public function __construct(
        private ExpedienteId $id,
        private string $numero,
        private string $titulo,
        private EstadoExpediente $estado,
        private \DateTimeImmutable $fechaApertura,
        private string $clientName = '',
        private string $caseReference = '',
        private string $folderPath = '',
        private string $paymentStatus = 'pending',
    ) {
    }

    public function id(): ExpedienteId
    {
        return $this->id;
    }

    public function numero(): string
    {
        return $this->numero;
    }

    public function titulo(): string
    {
        return $this->titulo;
    }

    public function estado(): EstadoExpediente
    {
        return $this->estado;
    }

    public function fechaApertura(): \DateTimeImmutable
    {
        return $this->fechaApertura;
    }

    public function clientName(): string
    {
        return $this->clientName;
    }

    public function caseReference(): string
    {
        return $this->caseReference;
    }

    public function folderPath(): string
    {
        return $this->folderPath;
    }

    public function paymentStatus(): string
    {
        return $this->paymentStatus;
    }
}
