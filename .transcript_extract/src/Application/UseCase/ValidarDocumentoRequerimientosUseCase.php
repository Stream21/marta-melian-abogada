<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\RequerimientosCompletitudValidator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class ValidarDocumentoRequerimientosUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private RequerimientosCompletitudValidator $completitudValidator,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(string $expedienteId, string $documentoRequeridoId): void
    {
        $id = new ExpedienteId($expedienteId);
        $this->completitudValidator->assertEnRequerimientos($id);

        $docReqId = new ExpedienteDocumentoRequeridoId($documentoRequeridoId);
        $doc = $this->completitudValidator->findDocumentoRequerido($id, $docReqId);

        $entrega = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento($id, $docReqId);
        if (null === $entrega || EstadoDocumentoEntregado::Entregado !== $entrega->estado()) {
            throw new \InvalidArgumentException('Solo puede validar documentos pendientes de revisión.');
        }

        $this->documentoEntregadoRepository->save($entrega->marcarValidado());

        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            return;
        }

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'documento_validado',
            sprintf('El abogado ha validado el documento «%s».', $doc->nombre()),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        if ($this->completitudValidator->requerimientosListo($id)) {
            $this->expedienteRepository->save(
                $expediente->withEstadoFase(EstadoFaseExpediente::RequerimientosListo)->touchEstadoCambio(),
            );

            $this->realtime->publishContratacionUpdate($id->value(), [
                'type' => 'requerimientos_listo',
                'actor' => 'sistema',
                'expedienteNumero' => $expediente->numero(),
                'clienteNombre' => $expediente->clientName(),
            ]);
        }

        $this->realtime->publishContratacionUpdate($id->value(), [
            'type' => 'documento_validado',
            'documentoId' => $documentoRequeridoId,
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }
}
