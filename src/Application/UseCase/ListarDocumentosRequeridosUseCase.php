<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\TramiteDocumentoRequerido;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteId;

final class ListarDocumentosRequeridosUseCase
{
    public function __construct(
        private TramiteDocumentoRequeridoRepositoryInterface $repository,
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

        $documentos = $this->repository->findByTramiteId($id);

        return array_map(static fn (TramiteDocumentoRequerido $doc): array => [
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
