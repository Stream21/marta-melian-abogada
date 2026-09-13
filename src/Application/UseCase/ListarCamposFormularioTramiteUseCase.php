<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\TramiteCampoFormulario;
use App\Domain\Repository\TramiteCampoFormularioRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteId;

final class ListarCamposFormularioTramiteUseCase
{
    public function __construct(
        private TramiteCampoFormularioRepositoryInterface $repository,
        private TramiteRepositoryInterface $tramiteRepository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(string $tramiteId): array
    {
        $id = new TramiteId($tramiteId);
        if (null === $this->tramiteRepository->findById($id)) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        return array_map(static fn (TramiteCampoFormulario $c): array => [
            'id' => $c->id()->value(),
            'clave' => $c->clave(),
            'etiqueta' => $c->etiqueta(),
            'tipo' => $c->tipo()->value,
            'tipoLabel' => $c->tipo()->label(),
            'opciones' => $c->opcionesJson(),
            'obligatorio' => $c->obligatorio(),
            'orden' => $c->orden(),
        ], $this->repository->findByTramiteId($id));
    }
}
