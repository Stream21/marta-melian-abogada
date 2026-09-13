<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ServicioCampoFormulario;
use App\Domain\Repository\ServicioCampoFormularioRepositoryInterface;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\ValueObject\ServicioId;

final class ListarCamposFormularioServicioUseCase
{
    public function __construct(
        private ServicioCampoFormularioRepositoryInterface $repository,
        private ServicioRepositoryInterface $servicioRepository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(string $servicioId): array
    {
        $id = new ServicioId($servicioId);
        if (null === $this->servicioRepository->findById($id)) {
            throw new \InvalidArgumentException('Servicio no encontrado.');
        }

        return array_map(static fn (ServicioCampoFormulario $c): array => [
            'id' => $c->id()->value(),
            'clave' => $c->clave(),
            'etiqueta' => $c->etiqueta(),
            'tipo' => $c->tipo()->value,
            'tipoLabel' => $c->tipo()->label(),
            'opciones' => $c->opcionesJson(),
            'obligatorio' => $c->obligatorio(),
            'orden' => $c->orden(),
        ], $this->repository->findByServicioId($id));
    }
}
