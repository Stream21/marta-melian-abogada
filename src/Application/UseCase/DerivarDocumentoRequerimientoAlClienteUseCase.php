<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\NotificarRequerimientosClienteService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ActorResponsableDocumento;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\SubidoPorDocumento;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoArchivoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class DerivarDocumentoRequerimientoAlClienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteDocumentoArchivoRepositoryInterface $archivoRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private NotificarRequerimientosClienteService $notificarCliente,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    public function __invoke(string $expedienteId, string $documentoId, string $nota = ''): void
    {
        $nota = trim($nota);
        if ('' !== $nota && mb_strlen($nota) < 5) {
            throw new \InvalidArgumentException('La nota debe tener al menos 5 caracteres si se indica.');
        }

        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de requerimientos.');
        }

        $docReqId = new ExpedienteDocumentoRequeridoId($documentoId);
        $doc = $this->documentoRequeridoRepository->findById($docReqId);
        if (null === $doc || !$doc->expedienteId()->equals($id)) {
            throw new \InvalidArgumentException('Documento requerido no encontrado.');
        }

        $entrega = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento($id, $docReqId);
        if (null === $entrega) {
            throw new \InvalidArgumentException('El abogado debe tomar o aportar el documento antes de derivarlo al cliente.');
        }

        if (ActorResponsableDocumento::Abogado !== $entrega->responsableActual()) {
            throw new \InvalidArgumentException('Este documento ya está pendiente del cliente.');
        }

        if (EstadoDocumentoEntregado::Validado === $entrega->estado()) {
            if (TipoDocumentoRequerido::Conjunto !== $doc->tipo() || SubidoPorDocumento::Abogado !== $entrega->subidoPor()) {
                throw new \InvalidArgumentException('Este documento validado no puede derivarse al cliente.');
            }
        } elseif (EstadoDocumentoEntregado::Entregado === $entrega->estado()) {
            throw new \InvalidArgumentException('Hay un documento pendiente de revisión.');
        } elseif (!in_array($entrega->estado(), [
            EstadoDocumentoEntregado::Pendiente,
            EstadoDocumentoEntregado::Rechazado,
        ], true)) {
            throw new \InvalidArgumentException('Este documento no puede derivarse al cliente en su estado actual.');
        }

        $tieneArchivos = [] !== $this->archivoRepository->findByEntregadoId($entrega->id())
            || '' !== trim($entrega->archivoPath());

        if (
            !$tieneArchivos
            && EstadoDocumentoEntregado::Pendiente === $entrega->estado()
            && ActorResponsableDocumento::Abogado !== $entrega->responsableActual()
        ) {
            throw new \InvalidArgumentException('Debe aportar al menos un archivo o tomar el requisito antes de derivarlo.');
        }

        $entrega = $entrega->derivarAlCliente();
        $this->documentoEntregadoRepository->save($entrega);

        $this->sincronizarEstadoExpediente($id, $expediente);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'documento_requerimientos_derivado_cliente',
            sprintf('El abogado ha derivado al cliente el documento «%s».', $doc->nombre()),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $clienteId = $expediente->clienteId();
        if (null !== $clienteId && '' !== $clienteId) {
            $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
            if (null !== $cliente) {
                $this->notificarCliente->notificarDocumentoDerivado($expediente, $cliente, $doc->nombre(), $nota);
            }
        }

        $this->realtime->publishContratacionUpdate($expedienteId, [
            'type' => 'documento_requerimientos_derivado_cliente',
            'documentoId' => $documentoId,
            'documentoNombre' => $doc->nombre(),
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);
    }

    private function sincronizarEstadoExpediente(ExpedienteId $id, \App\Domain\Entity\Expediente $expediente): void
    {
        if (EstadoFaseExpediente::RequerimientosListo === $expediente->estadoFase()) {
            $this->expedienteRepository->save(
                $expediente
                    ->withEstadoFase(EstadoFaseExpediente::RequerimientosEnProgreso)
                    ->touchEstadoCambio(),
            );
        }
    }
}
