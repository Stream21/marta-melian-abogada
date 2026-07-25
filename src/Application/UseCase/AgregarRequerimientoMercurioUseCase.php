<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\NotificarTramitacionClienteService;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\DestinoRequerimientoMercurio;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\ExpedienteRequerimientoMercurio;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\TipoRequerimientoMercurio;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class AgregarRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private NotificarTramitacionClienteService $notificar,
        private TramitacionSubfaseSyncService $subfaseSync,
    ) {
    }

    public function __invoke(
        string $expedienteId,
        string $tipo,
        string $destino,
        string $nombre,
        string $descripcion = '',
    ): string {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        $reqId = new ExpedienteRequerimientoMercurioId(bin2hex(random_bytes(16)));
        $requerimiento = ExpedienteRequerimientoMercurio::crear(
            $reqId,
            $id,
            TipoRequerimientoMercurio::fromString($tipo),
            DestinoRequerimientoMercurio::fromString($destino),
            $nombre,
            $descripcion,
        );
        $this->requerimientoRepository->save($requerimiento);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'requerimiento_mercurio_anadido',
            sprintf('Requerimiento Mercurio añadido: %s (%s).', $requerimiento->nombre(), $requerimiento->destino()->label()),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->subfaseSync->sync($expediente);

        if (
            DestinoRequerimientoMercurio::Cliente === $requerimiento->destino()
            && null !== $expediente->clienteId()
            && '' !== $expediente->clienteId()
        ) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $this->notificar->notificarRequerimientoCliente(
                    $expediente,
                    $cliente,
                    $requerimiento->nombre(),
                    $descripcion,
                );
            }
        }

        return $reqId->value();
    }
}
