<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Tramite;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ServicioId;

final class ListarTramitesUseCase
{
    public function __construct(
        private TramiteRepositoryInterface $repository,
    ) {
    }

    /**
     * @return Tramite[]
     */
    public function __invoke(bool $incluirInactivos = false, ?string $servicioId = null): array
    {
        $filtroServicio = null !== $servicioId && '' !== trim($servicioId)
            ? new ServicioId($servicioId)
            : null;

        return $this->repository->findAll($incluirInactivos, $filtroServicio);
    }
}
