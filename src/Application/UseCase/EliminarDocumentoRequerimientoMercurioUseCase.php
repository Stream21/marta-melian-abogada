<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteRequerimientoDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoDocumentoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class EliminarDocumentoRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteRequerimientoDocumentoRepositoryInterface $documentoRepository,
    ) {
    }

    public function __invoke(string $expedienteId, string $requerimientoId, string $documentoId): void
    {
        $expId = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($expId);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        $reqId = new ExpedienteRequerimientoMercurioId($requerimientoId);
        $req = $this->requerimientoRepository->findById($reqId);
        if (null === $req || !$req->expedienteId()->equals($expId)) {
            throw new \InvalidArgumentException('Requerimiento no encontrado.');
        }
        if (!$req->estado()->estaAbierto()) {
            throw new \InvalidArgumentException('El requerimiento ya está cerrado o presentado.');
        }

        $doc = $this->documentoRepository->findById(new ExpedienteRequerimientoDocumentoId($documentoId));
        if (null === $doc || $doc->requerimientoId()->value() !== $reqId->value()) {
            throw new \InvalidArgumentException('Documento de requerimiento no encontrado.');
        }

        $this->documentoRepository->delete($doc->id());
    }
}
