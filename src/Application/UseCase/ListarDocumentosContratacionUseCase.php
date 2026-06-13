<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class ListarDocumentosContratacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private TramiteDocumentoRequeridoRepositoryInterface $documentoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(string $expedienteId): array
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (null === $expediente->tramiteId()) {
            return [];
        }

        $entregados = [];
        foreach ($this->documentoEntregadoRepository->findByExpediente($expediente->id()) as $doc) {
            $entregados[$doc->documentoRequeridoId()->value()] = $doc;
        }

        $items = [];
        foreach ($this->documentoRepository->findByTramiteId(new TramiteId($expediente->tramiteId())) as $doc) {
            if ($doc->fase() !== FaseDocumentoTramite::DocumentacionBasica) {
                continue;
            }

            $entregado = $entregados[$doc->id()->value()] ?? null;
            $items[] = [
                'id' => $doc->id()->value(),
                'nombre' => $doc->nombre(),
                'descripcion' => $doc->descripcion(),
                'obligatorio' => $doc->obligatorio(),
                'tipo' => $doc->tipo()->value,
                'maxImagenes' => $doc->maxImagenes(),
                'estado' => $entregado?->estado()->value ?? 'pendiente',
                'entregadoAt' => $entregado?->entregadoAt()->format(\DateTimeInterface::ATOM),
                'archivoPath' => $entregado?->archivoPath(),
            ];
        }

        return $items;
    }
}
