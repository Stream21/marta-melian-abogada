<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ServicioDocumentoRequerido;
use App\Domain\Repository\ServicioDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\ValueObject\ServicioId;

final class ListarDocumentosRequeridosServicioUseCase
{
    public function __construct(
        private ServicioDocumentoRequeridoRepositoryInterface $repository,
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

        $documentos = $this->repository->findByServicioId($id);

        return array_map(static fn (ServicioDocumentoRequerido $doc): array => [
            'id' => $doc->id()->value(),
            'fase' => $doc->fase()->value,
            'nombre' => $doc->nombre(),
            'descripcion' => $doc->descripcion(),
            'obligatorio' => $doc->obligatorio(),
            'tipo' => $doc->tipo()->value,
            'maxImagenes' => $doc->maxImagenes(),
            'orden' => $doc->orden(),
            'formatoEntrega' => 'pdf',
        ], $documentos);
    }
}
