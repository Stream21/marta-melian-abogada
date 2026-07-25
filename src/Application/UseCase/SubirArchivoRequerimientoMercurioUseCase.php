<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\DestinoRequerimientoMercurio;
use App\Domain\Entity\EstadoRequerimientoMercurio;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class SubirArchivoRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private TramitacionSubfaseSyncService $subfaseSync,
    ) {
    }

    /**
     * @param array{content: string, filename: string} $file
     */
    public function __invoke(
        string $expedienteId,
        string $requerimientoId,
        array $file,
        bool $desdePortalCliente = false,
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

        if ($desdePortalCliente) {
            if (DestinoRequerimientoMercurio::Cliente !== $req->destino()) {
                throw new \InvalidArgumentException('Este requerimiento no está dirigido al cliente.');
            }
            if (EstadoRequerimientoMercurio::PendienteCliente !== $req->estado()) {
                throw new \InvalidArgumentException('El requerimiento ya no admite subida por el cliente.');
            }
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $file['filename']) ?: 'documento.pdf';
        $path = $this->fileStorage->savePdf(
            $id,
            'req-mercurio-' . substr($requerimientoId, 0, 8) . '-' . $safeName,
            $file['content'],
        );

        $actualizado = $req->withArchivo($path, $file['filename']);
        $this->requerimientoRepository->save($actualizado);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'requerimiento_mercurio_archivo',
            sprintf(
                'Archivo adjunto al requerimiento «%s»%s.',
                $req->nombre(),
                $desdePortalCliente ? ' (cliente)' : '',
            ),
            $desdePortalCliente ? ActorHitoExpediente::Cliente : ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->subfaseSync->sync($expediente);
    }
}
