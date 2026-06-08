<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\CrearServicioInput;
use App\Domain\Entity\AreaJuridicaCatalog;
use App\Domain\Entity\Servicio;
use App\Domain\Entity\TipoServicio;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\ValueObject\ServicioId;

final class CrearServicioUseCase
{
    public function __construct(
        private ServicioRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CrearServicioInput $input): Servicio
    {
        $nombre = trim($input->nombre);
        if ('' === $nombre) {
            throw new \InvalidArgumentException('El nombre es obligatorio.');
        }

        $normalized = mb_strtolower($nombre);
        if (null !== $this->repository->findByNombreNormalized($normalized)) {
            throw new \InvalidArgumentException('Ya existe un servicio con el mismo nombre.');
        }

        $tipo = TipoServicio::fromString($input->tipo);
        $areaId = AreaJuridicaCatalog::idFromCodigo($tipo->value);

        $servicio = new Servicio(
            ServicioId::generate(),
            $nombre,
            $areaId,
            true,
            $tipo->value,
            $tipo->label(),
        );
        $this->repository->save($servicio);

        return $this->repository->findById($servicio->id()) ?? $servicio;
    }
}
