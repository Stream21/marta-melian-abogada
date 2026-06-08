<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ActualizarServicioInput;
use App\Domain\Entity\AreaJuridicaCatalog;
use App\Domain\Entity\Servicio;
use App\Domain\Entity\TipoServicio;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\ValueObject\ServicioId;

final class ActualizarServicioUseCase
{
    public function __construct(
        private ServicioRepositoryInterface $repository,
    ) {
    }

    public function __invoke(ActualizarServicioInput $input): Servicio
    {
        $nombre = trim($input->nombre);
        if ('' === $nombre) {
            throw new \InvalidArgumentException('El nombre es obligatorio.');
        }

        $id = new ServicioId($input->id);
        $existing = $this->repository->findById($id);
        if (null === $existing) {
            throw new \InvalidArgumentException('Servicio no encontrado.');
        }

        $normalized = mb_strtolower($nombre);
        if (null !== $this->repository->findByNombreNormalized($normalized, $id)) {
            throw new \InvalidArgumentException('Ya existe un servicio con el mismo nombre.');
        }

        $tipo = TipoServicio::fromString($input->tipo);
        $areaId = AreaJuridicaCatalog::idFromCodigo($tipo->value);

        $updated = $existing->withDatos(
            $nombre,
            $areaId,
            $tipo->value,
            $tipo->label(),
        );
        $this->repository->save($updated);

        return $this->repository->findById($id) ?? $updated;
    }
}
