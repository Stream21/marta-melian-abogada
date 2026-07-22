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
        private PaymentHoldedEstado $holdedEstado = PaymentHoldedEstado::NoAplica,
        private ?string $holdedSyncError = null,
        private ?\DateTimeImmutable $holdedSyncedAt = null,
        private ?int $cuotaNumero = null,
    ) {
    }

    public function cuotaNumero(): ?int
    {
        return $this->cuotaNumero;
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

    public function invoicePdfUrl(): ?string
    {
        if (PaymentHoldedEstado::Sincronizado !== $this->holdedEstado || null === $this->pdfPath || '' === $this->pdfPath) {
            return null;
        }

        return '/api/expedientes/' . $this->expedienteId->value() . '/invoices/' . $this->id->value() . '/pdf';
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function holdedEstado(): PaymentHoldedEstado
    {
        return $this->holdedEstado;
    }

    public function holdedSyncError(): ?string
    {
        return $this->holdedSyncError;
    }

    public function holdedSyncedAt(): ?\DateTimeImmutable
    {
        return $this->holdedSyncedAt;
    }

    public function withStatus(PaymentStatus $status): self
    {
        return new self(
            $this->id,
            $this->expedienteId,
            $status,
            $this->type,
            $this->holdedInvoiceId,
            $this->stripeSessionId,
            $this->amount,
            $this->pdfPath,
            $this->createdAt,
            new \DateTimeImmutable('now'),
            $this->holdedEstado,
            $this->holdedSyncError,
            $this->holdedSyncedAt,
            $this->cuotaNumero,
        );
    }

    public function withHoldedSync(
        PaymentHoldedEstado $estado,
        ?string $holdedInvoiceId = null,
        ?string $pdfPath = null,
        ?string $syncError = null,
        ?\DateTimeImmutable $syncedAt = null,
    ): self {
        $error = null;
        if (null !== $syncError && '' !== trim($syncError)) {
            $limpio = trim(preg_replace('/\s+/u', ' ', strip_tags($syncError)) ?? $syncError);
            $error = mb_strlen($limpio) <= 500 ? $limpio : mb_substr($limpio, 0, 499) . '…';
        }

        return new self(
            $this->id,
            $this->expedienteId,
            $this->status,
            $this->type,
            $holdedInvoiceId ?? $this->holdedInvoiceId,
            $this->stripeSessionId,
            $this->amount,
            $pdfPath ?? $this->pdfPath,
            $this->createdAt,
            new \DateTimeImmutable('now'),
            $estado,
            $error,
            $syncedAt ?? $this->holdedSyncedAt,
            $this->cuotaNumero,
        );
    }

    public static function defaultHoldedEstadoForType(PaymentType $type): PaymentHoldedEstado
    {
        return match ($type) {
            PaymentType::Manual => PaymentHoldedEstado::NoAplica,
            PaymentType::Link, PaymentType::Installment => PaymentHoldedEstado::PendienteSync,
        };
    }
}
