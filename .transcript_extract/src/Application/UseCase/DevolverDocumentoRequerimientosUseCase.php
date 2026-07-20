<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\NotificarExpedienteClienteService;
use App\Application\Service\RequerimientosCompletitudValidator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class DevolverDocumentoRequerimientosUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private RequerimientosCompletitudValidator $completitudValidator,
        private ContratacionRepositoryInterface $contratacionRepository,
        private NotificarExpedienteClienteService $notificarCliente,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(string $expedienteId, string $documentoRequeridoId, string $nota): void
    {
        $nota = trim($nota);
        if (mb_strlen($nota) < 5) {
            throw new \InvalidArgumentException('Escriba una nota de al menos 5 caracteres para el cliente.');
        }

        $id = new ExpedienteId($expedienteId);
        $this->completitudValidator->assertEnRequerimientos($id);

        $docReqId = new ExpedienteDocumentoRequeridoId($documentoRequeridoId);
        $doc = $this->completitudValidator->findDocumentoRequerido($id, $docReqId);

        $entrega = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento($id, $docReqId);
        if (null === $entrega || EstadoDocumentoEntregado::Entregado !== $entrega->estado()) {
            throw new \InvalidArgumentException('Solo puede devolver documentos pendientes de revisión.');
        }

        $this->documentoEntregadoRepository->save($entrega->marcarRechazado($nota));

        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            return;
        }

        if (EstadoFaseExpediente::RequerimientosListo === $expediente->estadoFase()) {
            $this->expedienteRepository->save(
                $expediente->withEstadoFase(EstadoFaseExpediente::RequerimientosEnProgreso)->touchEstadoCambio(),
            );
            $expediente = $this->expedienteRepository->findById($id) ?? $expediente;
        }

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'documento_devuelto',
            sprintf('El abogado ha devuelto el documento «%s»: %s', $doc->nombre(), $nota),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $clienteId = $expediente->clienteId();
        if (null !== $clienteId && '' !== $clienteId) {
            $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
            if (null !== $cliente) {
                $this->notificarCliente->notificarDocumentoDevuelto($expediente, $cliente, $doc->nombre(), $nota);
            }
        }

        $this->realtime->publishContratacionUpdate($id->value(), [
            'type' => 'documento_devuelto',
            'documentoId' => $documentoRequeridoId,
            'nota' => $nota,
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }
}
