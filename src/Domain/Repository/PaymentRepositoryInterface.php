<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Payment;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\PaymentId;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;

    public function findById(PaymentId $id): ?Payment;

    /**
     * @return Payment[]
     */
    public function findByExpediente(ExpedienteId $expedienteId): array;

    /**
     * @return Payment[]
     */
    public function findAll(): array;

    public function findByStripeSessionId(string $stripeSessionId): ?Payment;
}
