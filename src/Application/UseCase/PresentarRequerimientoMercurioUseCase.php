<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\NotificarTramitacionClienteService;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class PresentarRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private NotificarTramitacionClienteService $notificar,
        private TramitacionSubfaseSyncService $subfaseSync,
    ) {
    }

    /**
     * @param array{content: string, filename: string} $justificanteFile
     * @param array{content: string, filename: string}|null $archivoFile
     */
    public function __invoke(
        string $expedienteId,
        string $requerimientoId,
        array $justificanteFile,
        ?array $archivoFile = null,
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

        $archivoPath = null;
        $archivoNombre = null;
        if (null !== $archivoFile) {
            $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $archivoFile['filename']) ?: 'documento.pdf';
            $archivoPath = $this->fileStorage->savePdf(
                $id,
                'req-mercurio-' . substr($requerimientoId, 0, 8) . '-' . $safeName,
                $archivoFile['content'],
            );
            $archivoNombre = $archivoFile['filename'];
        }

        $justSafe = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $justificanteFile['filename']) ?: 'justificante.pdf';
        $justificantePath = $this->fileStorage->savePdf(
            $id,
            'req-mercurio-just-' . substr($requerimientoId, 0, 8) . '-' . $justSafe,
            $justificanteFile['content'],
        );

        $cerrado = $req->marcarPresentado($justificantePath, $archivoPath, $archivoNombre);
        $this->requerimientoRepository->save($cerrado);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'requerimiento_mercurio_presentado',
            sprintf('Requerimiento Mercurio «%s» presentado en plataforma (justificante adjunto).', $req->nombre()),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $actualizado = $this->subfaseSync->sync($expediente);

        if (
            null !== $expediente->clienteId()
            && '' !== $expediente->clienteId()
            && 0 === $this->requerimientoRepository->countAbiertosByExpediente($id)
        ) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $this->notificar->notificarVueltaSeguimiento($actualizado, $cliente);
            }
        }
    }
}
