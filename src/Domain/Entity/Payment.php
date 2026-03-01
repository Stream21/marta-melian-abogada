<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\PaymentId;

final readonly class Payment
{
    public function __construct(
        private PaymentId $id,
        private ExpedienteId $expedienteId,
        private PaymentStatus $status,
        private PaymentType $type,
        private ?string $holdedInvoiceId,
        private ?string $stripeSessionId,
        private string $amount,
        private ?string $pdfPath,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt,
    ) {
    }

    public function id(): PaymentId
    {
        return $this->id;
    }

    public function expedienteId(): ExpedienteId
    {
        return $this->expedienteId;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function type(): PaymentType
    {
        return $this->type;
    }

    public function holdedInvoiceId(): ?string
    {
        return $this->holdedInvoiceId;
    }

    public function stripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function pdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
