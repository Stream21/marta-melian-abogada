<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\PaymentId;
use App\Infrastructure\Persistence\Doctrine\Entity\PaymentOrm;
use Doctrine\ORM\EntityManagerInterface;

final class PaymentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Payment $payment): void
    {
        $existing = $this->entityManager->getRepository(PaymentOrm::class)->find($payment->id()->value());

        if ($existing instanceof PaymentOrm) {
            $existing->setStatus($payment->status()->value);
            $existing->setHoldedInvoiceId($payment->holdedInvoiceId());
            $existing->setPdfPath($payment->pdfPath());
            $existing->setUpdatedAt($payment->updatedAt());
        } else {
            $this->entityManager->persist($this->domainToOrm($payment));
        }

        $this->entityManager->flush();
    }

    public function findById(PaymentId $id): ?Payment
    {
        $orm = $this->entityManager->getRepository(PaymentOrm::class)->find($id->value());

        return $orm instanceof PaymentOrm ? $this->ormToDomain($orm) : null;
    }

    /**
     * @return Payment[]
     */
    public function findByExpediente(ExpedienteId $expedienteId): array
    {
        $orms = $this->entityManager->getRepository(PaymentOrm::class)->findBy(
            ['expedienteId' => $expedienteId->value()],
            ['createdAt' => 'DESC'],
        );

        return array_map($this->ormToDomain(...), $orms);
    }

    /**
     * @return Payment[]
     */
    public function findAll(): array
    {
        $orms = $this->entityManager->getRepository(PaymentOrm::class)->findBy([], ['createdAt' => 'DESC']);

        return array_map($this->ormToDomain(...), $orms);
    }

    public function findByStripeSessionId(string $stripeSessionId): ?Payment
    {
        $orm = $this->entityManager->getRepository(PaymentOrm::class)->findOneBy(['stripeSessionId' => $stripeSessionId]);

        return $orm instanceof PaymentOrm ? $this->ormToDomain($orm) : null;
    }

    private function ormToDomain(PaymentOrm $orm): Payment
    {
        return new Payment(
            new PaymentId($orm->getId()),
            new ExpedienteId($orm->getExpedienteId()),
            PaymentStatus::from($orm->getStatus()),
            PaymentType::from($orm->getType()),
            $orm->getHoldedInvoiceId(),
            $orm->getStripeSessionId(),
            $orm->getAmount(),
            $orm->getPdfPath(),
            $orm->getCreatedAt(),
            $orm->getUpdatedAt(),
        );
    }

    private function domainToOrm(Payment $payment): PaymentOrm
    {
        $orm = new PaymentOrm();
        $orm->setId($payment->id()->value());
        $orm->setExpedienteId($payment->expedienteId()->value());
        $orm->setStatus($payment->status()->value);
        $orm->setType($payment->type()->value);
        $orm->setHoldedInvoiceId($payment->holdedInvoiceId());
        $orm->setStripeSessionId($payment->stripeSessionId());
        $orm->setAmount($payment->amount());
        $orm->setPdfPath($payment->pdfPath());
        $orm->setCreatedAt($payment->createdAt());
        $orm->setUpdatedAt($payment->updatedAt());

        return $orm;
    }
}
