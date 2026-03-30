<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\CrearTipoCasoInput;
use App\Domain\Entity\TipoCaso;
use App\Domain\Repository\TipoCasoRepositoryInterface;
use App\Domain\ValueObject\TipoCasoId;

final class CrearTipoCasoUseCase
{
    public function __construct(
        private TipoCasoRepositoryInterface $repository,
    ) {
    }

    public function __invoke(CrearTipoCasoInput $input): TipoCaso
    {
        $nombre = trim($input->nombre);
        if ('' === $nombre) {
            throw new \InvalidArgumentException('El nombre es obligatorio.');
        }

        $normalized = mb_strtolower($nombre);
        if (null !== $this->repository->findByNombreNormalized($normalized)) {
            throw new \InvalidArgumentException('Ya existe un tipo de caso con el mismo nombre.');
        }

        $tipo = new TipoCaso(
            TipoCasoId::generate(),
            $nombre,
            trim($input->descripcion),
        );
        $this->repository->save($tipo);

        return $tipo;
    }
}
