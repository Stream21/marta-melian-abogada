<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Port\DocumentToPdfConverterPort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\NotificarExpedienteClienteService;
use App\Application\Service\RequerimientosCompletitudValidator;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\OrigenDocumentoRequeridoExpediente;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;

final class AgregarDocumentoRequerimientoExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private NotificarExpedienteClienteService $notificarCliente,
        private RequerimientosCompletitudValidator $completitudValidator,
        private ContratacionRealtimePort $realtime,
    ) {
    }

    /**
     * @return array{id: string}
     */
    public function __invoke(
        string $expedienteId,
        string $nombre,
        string $descripcion,
        bool $obligatorio,
        string $tipo,
        int $maxImagenes,
    ): array {
        $id = new ExpedienteId($expedienteId);
        $this->completitudValidator->assertEnRequerimientos($id);

        $nombre = trim($nombre);
        if ('' === $nombre) {
            throw new \InvalidArgumentException('El nombre del documento es obligatorio.');
        }

        $tipoEnum = TipoDocumentoRequerido::tryFrom($tipo) ?? TipoDocumentoRequerido::Individual;
        $maxImagenes = $tipoEnum === TipoDocumentoRequerido::Individual ? 1 : max(2, min(10, $maxImagenes));

        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $orden = $this->documentoRequeridoRepository->countByExpediente($id);
        $docId = new ExpedienteDocumentoRequeridoId(bin2hex(random_bytes(16)));

        $this->documentoRequeridoRepository->save(new ExpedienteDocumentoRequerido(
            $docId,
            $id,
            $nombre,
            trim($descripcion),
            $obligatorio,
            $tipoEnum,
            $maxImagenes,
            $orden,
            OrigenDocumentoRequeridoExpediente::Manual,
        ));

        if (EstadoFaseExpediente::RequerimientosListo === $expediente->estadoFase()) {
            $this->expedienteRepository->save(
                $expediente->withEstadoFase(EstadoFaseExpediente::RequerimientosEnProgreso)->touchEstadoCambio(),
            );
        }

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'documento_requerido_anadido',
            sprintf('El abogado ha añadido el documento requerido «%s».', $nombre),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $clienteId = $expediente->clienteId();
        if (null !== $clienteId && '' !== $clienteId) {
            $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
            if (null !== $cliente) {
                $this->notificarCliente->notificarNuevoDocumentoRequerido($expediente, $cliente, $nombre, $descripcion);
            }
        }

        $this->realtime->publishContratacionUpdate($id->value(), [
            'type' => 'documento_requerido_anadido',
            'documentoId' => $docId->value(),
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);

        return ['id' => $docId->value()];
    }
}
