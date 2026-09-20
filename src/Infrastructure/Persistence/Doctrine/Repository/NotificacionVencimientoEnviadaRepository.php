<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Entity\NotificacionVencimientoEnviada;
use App\Domain\Repository\NotificacionVencimientoEnviadaRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Infrastructure\Persistence\Doctrine\Entity\NotificacionVencimientoEnviadaOrm;
use Doctrine\ORM\EntityManagerInterface;

final class NotificacionVencimientoEnviadaRepository implements NotificacionVencimientoEnviadaRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function exists(ExpedienteId $expedienteId, \DateTimeImmutable $fechaVencimiento, int $diaRelativo): bool
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(n.id)')
            ->from(NotificacionVencimientoEnviadaOrm::class, 'n')
            ->where('n.expedienteId = :expedienteId')
            ->andWhere('n.fechaVencimiento = :fecha')
            ->andWhere('n.diaRelativo = :dia')
            ->setParameter('expedienteId', $expedienteId->value())
            ->setParameter('fecha', $fechaVencimiento->setTime(0, 0))
            ->setParameter('dia', $diaRelativo)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function save(NotificacionVencimientoEnviada $registro): void
    {
        $orm = new NotificacionVencimientoEnviadaOrm(
            $registro->id(),
            $registro->expedienteId()->value(),
            $registro->fechaVencimiento()->setTime(0, 0),
            $registro->diaRelativo(),
            $registro->enviadoAt(),
        );
        $this->em->persist($orm);
        $this->em->flush();
    }
}
