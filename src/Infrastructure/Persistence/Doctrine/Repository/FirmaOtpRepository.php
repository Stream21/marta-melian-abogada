<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Repository\FirmaOtpRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Infrastructure\Persistence\Doctrine\Entity\ExpedienteFirmaOtpOrm;
use Doctrine\ORM\EntityManagerInterface;

final class FirmaOtpRepository implements FirmaOtpRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function savePending(
        ExpedienteId $expedienteId,
        string $codigoHash,
        string $telefono,
        \DateTimeImmutable $expiresAt,
    ): string {
        $id = bin2hex(random_bytes(16));
        $now = new \DateTimeImmutable();

        $orm = new ExpedienteFirmaOtpOrm();
        $orm->setId($id);
        $orm->setExpedienteId($expedienteId->value());
        $orm->setCodigoHash($codigoHash);
        $orm->setTelefono($telefono);
        $orm->setExpiresAt($expiresAt);
        $orm->setCreatedAt($now);

        $this->entityManager->persist($orm);
        $this->entityManager->flush();

        return $id;
    }

    public function findLatestPending(ExpedienteId $expedienteId): ?array
    {
        $orm = $this->entityManager->getRepository(ExpedienteFirmaOtpOrm::class)
            ->createQueryBuilder('o')
            ->where('o.expedienteId = :expedienteId')
            ->andWhere('o.verifiedAt IS NULL')
            ->setParameter('expedienteId', $expedienteId->value())
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$orm instanceof ExpedienteFirmaOtpOrm) {
            return null;
        }

        return [
            'id' => $orm->getId(),
            'codigoHash' => $orm->getCodigoHash(),
            'telefono' => $orm->getTelefono(),
            'expiresAt' => $orm->getExpiresAt(),
            'intentosFallidos' => $orm->getIntentosFallidos(),
        ];
    }

    public function incrementIntentos(string $otpId): void
    {
        $orm = $this->entityManager->find(ExpedienteFirmaOtpOrm::class, $otpId);
        if (!$orm instanceof ExpedienteFirmaOtpOrm) {
            return;
        }

        $orm->setIntentosFallidos($orm->getIntentosFallidos() + 1);
        $this->entityManager->flush();
    }

    public function markVerified(string $otpId, \DateTimeImmutable $verifiedAt): void
    {
        $orm = $this->entityManager->find(ExpedienteFirmaOtpOrm::class, $otpId);
        if (!$orm instanceof ExpedienteFirmaOtpOrm) {
            return;
        }

        $orm->setVerifiedAt($verifiedAt);
        $this->entityManager->flush();
    }

    public function hasValidSession(ExpedienteId $expedienteId, \DateTimeImmutable $now, int $sessionMinutes): bool
    {
        $desde = $now->modify(sprintf('-%d minutes', $sessionMinutes));

        $count = (int) $this->entityManager->getRepository(ExpedienteFirmaOtpOrm::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.expedienteId = :expedienteId')
            ->andWhere('o.verifiedAt IS NOT NULL')
            ->andWhere('o.verifiedAt >= :desde')
            ->setParameter('expedienteId', $expedienteId->value())
            ->setParameter('desde', $desde)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
