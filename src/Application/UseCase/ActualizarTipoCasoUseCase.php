<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ActualizarTipoCasoInput;
use App\Domain\Entity\TipoCaso;
use App\Domain\Repository\TipoCasoRepositoryInterface;
use App\Domain\ValueObject\TipoCasoId;

final class ActualizarTipoCasoUseCase
{
    public function __construct(
        private TipoCasoRepositoryInterface $repository,
    ) {
    }

    public function __invoke(ActualizarTipoCasoInput $input): TipoCaso
    {
        $nombre = trim($input->nombre);
        if ('' === $nombre) {
            throw new \InvalidArgumentException('El nombre es obligatorio.');
        }

        $id = new TipoCasoId($input->id);
        $existing = $this->repository->findById($id);
        if (null === $existing) {
            throw new \InvalidArgumentException('Tipo de caso no encontrado.');
        }

        $normalized = mb_strtolower($nombre);
        if (null !== $this->repository->findByNombreNormalized($normalized, $id)) {
            throw new \InvalidArgumentException('Ya existe un tipo de caso con el mismo nombre.');
        }

        $updated = $existing->withNombreDescripcion(
            $nombre,
            trim($input->descripcion),
        );
        $this->repository->save($updated);

        return $updated;
    }
}
