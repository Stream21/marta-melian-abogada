<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\NotificarExpedienteClienteService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\OrigenDocumentoRequeridoExpediente;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
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
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private NotificarExpedienteClienteService $notificarCliente,
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
        $nombre = trim($nombre);
        if ('' === $nombre) {
            throw new \InvalidArgumentException('El nombre del documento es obligatorio.');
        }

        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Requerimientos !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de requerimientos.');
        }

        try {
            $tipoEnum = TipoDocumentoRequerido::fromString($tipo);
        } catch (\InvalidArgumentException) {
            $tipoEnum = TipoDocumentoRequerido::Individual;
        }
        $docs = $this->documentoRepository->findByExpediente($id);
        $orden = count($docs);

        $documento = new ExpedienteDocumentoRequerido(
            new ExpedienteDocumentoRequeridoId(bin2hex(random_bytes(16))),
            $id,
            $nombre,
            trim($descripcion),
            $obligatorio,
            $tipoEnum,
            max(1, $maxImagenes),
            $orden,
            OrigenDocumentoRequeridoExpediente::Manual,
        );

        $this->documentoRepository->save($documento);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'documento_requerido_anadido',
            sprintf('Se ha añadido el documento requerido «%s».', $nombre),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $clienteId = $expediente->clienteId();
        if (null !== $clienteId && '' !== $clienteId) {
            $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
            if (null !== $cliente) {
                $this->notificarCliente->notificarNuevoDocumentoRequerido($expediente, $cliente->email(), $documento);
            }
        }

        $this->realtime->publishContratacionUpdate($id->value(), [
            'type' => 'documento_requerido_anadido',
            'documentoId' => $documento->id()->value(),
            'documentoNombre' => $documento->nombre(),
            'actor' => 'abogado',
            'expedienteNumero' => $expediente->numero(),
            'clienteNombre' => $expediente->clientName(),
        ]);

        return ['id' => $documento->id()->value()];
    }
}
