<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\NotificacionVencimientoEnviada;
use App\Domain\ValueObject\ExpedienteId;

interface NotificacionVencimientoEnviadaRepositoryInterface
{
    public function exists(ExpedienteId $expedienteId, \DateTimeImmutable $fechaVencimiento, int $diaRelativo): bool;

    public function save(NotificacionVencimientoEnviada $registro): void;
}
