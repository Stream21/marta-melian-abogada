<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ActualizarTramiteInput;
use App\Domain\Entity\PlataformaTramitacion;
use App\Domain\Entity\Tramite;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ServicioId;
use App\Domain\ValueObject\TramiteId;

final class ActualizarTramiteUseCase
{
    public function __construct(
        private TramiteRepositoryInterface $tramiteRepository,
        private ServicioRepositoryInterface $servicioRepository,
    ) {
    }

    public function __invoke(ActualizarTramiteInput $input): Tramite
    {
        $nombre = trim($input->nombre);
        if ('' === $nombre) {
            throw new \InvalidArgumentException('El nombre es obligatorio.');
        }

        $id = new TramiteId($input->id);
        $existing = $this->tramiteRepository->findById($id);
        if (null === $existing) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $servicioId = new ServicioId($input->servicioId);
        $servicio = $this->servicioRepository->findById($servicioId);
        if (null === $servicio) {
            throw new \InvalidArgumentException('Servicio no encontrado.');
        }
        if (!$servicio->activo() && $existing->servicioId()->value() !== $servicioId->value()) {
            throw new \InvalidArgumentException('No se puede asignar un trámite a un servicio inactivo.');
        }

        if ($input->honorarios <= 0) {
            throw new \InvalidArgumentException('Los honorarios deben ser mayores que cero.');
        }

        $normalized = mb_strtolower($nombre);
        if (null !== $this->tramiteRepository->findByNombreNormalized($servicioId, $normalized, $id)) {
            throw new \InvalidArgumentException('Ya existe un trámite con el mismo nombre en este servicio.');
        }

        $plataforma = PlataformaTramitacion::fromString($input->plataforma);

        $updated = $existing->withDatos(
            $servicioId,
            $nombre,
            $input->honorarios,
            $plataforma,
            $input->requiereProcurador,
        );
        $this->tramiteRepository->save($updated);

        return $this->tramiteRepository->findById($id) ?? $updated;
    }
}
