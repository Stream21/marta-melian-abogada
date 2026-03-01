<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payment')]
class PaymentOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $expedienteId;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $type;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $holdedInvoiceId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $amount;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $pdfPath = null;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getHoldedInvoiceId(): ?string
    {
        return $this->holdedInvoiceId;
    }

    public function setHoldedInvoiceId(?string $holdedInvoiceId): void
    {
        $this->holdedInvoiceId = $holdedInvoiceId;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): void
    {
        $this->stripeSessionId = $stripeSessionId;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): void
    {
        $this->pdfPath = $pdfPath;
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
