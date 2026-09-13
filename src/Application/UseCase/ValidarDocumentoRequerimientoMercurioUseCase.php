<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoDocumentoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class ValidarDocumentoRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteRequerimientoDocumentoRepositoryInterface $documentoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private TramitacionSubfaseSyncService $subfaseSync,
    ) {
    }

    public function __invoke(
        string $expedienteId,
        string $requerimientoId,
        string $documentoId,
        string $accion,
        string $notaRechazo = '',
    ): void {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        $req = $this->requerimientoRepository->findById(new ExpedienteRequerimientoMercurioId($requerimientoId));
        if (null === $req || $req->expedienteId()->value() !== $id->value()) {
            throw new \InvalidArgumentException('Requerimiento no encontrado.');
        }

        $doc = $this->documentoRepository->findById(new ExpedienteRequerimientoDocumentoId($documentoId));
        if (null === $doc || $doc->requerimientoId()->value() !== $requerimientoId) {
            throw new \InvalidArgumentException('Documento de requerimiento no encontrado.');
        }

        $actualizado = match ($accion) {
            'validar' => $doc->validar(),
            'rechazar' => $doc->rechazar($notaRechazo),
            default => throw new \InvalidArgumentException('Acción no válida (validar o rechazar).'),
        };

        $this->documentoRepository->save($actualizado);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'validar' === $accion ? 'requerimiento_mercurio_doc_validado' : 'requerimiento_mercurio_doc_rechazado',
            sprintf(
                'Documento «%s» %s en requerimiento «%s».',
                $doc->nombre(),
                'validar' === $accion ? 'validado' : 'devuelto',
                $req->nombre(),
            ),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->subfaseSync->sync($expediente);
    }
}
