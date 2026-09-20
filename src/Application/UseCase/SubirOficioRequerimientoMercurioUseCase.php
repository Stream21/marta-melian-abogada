<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\NotificarTramitacionClienteService;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\ResponsableRequerimientoDocumento;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoCampoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class SubirOficioRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteRequerimientoDocumentoRepositoryInterface $documentoRepository,
        private ExpedienteRequerimientoCampoRepositoryInterface $campoRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private NotificarTramitacionClienteService $notificar,
        private TramitacionSubfaseSyncService $subfaseSync,
    ) {
    }

    /**
     * @param array{content: string, filename: string} $file
     */
    public function __invoke(string $expedienteId, string $requerimientoId, array $file): void
    {
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
        if (!$req->estado()->estaAbierto()) {
            throw new \InvalidArgumentException('El requerimiento ya está cerrado o presentado.');
        }

        $yaTeniaOficio = $req->tieneOficio();
        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $file['filename']) ?: 'requerimiento.pdf';
        $path = $this->fileStorage->savePdf(
            $id,
            'req-oficio-' . substr($requerimientoId, 0, 8) . '-' . $safeName,
            $file['content'],
        );

        $actualizado = $req->withOficio($path, $file['filename']);
        if ($this->tieneTrabajoParaCliente($actualizado)) {
            $actualizado = $actualizado->abrirParaCliente();
        }
        $this->requerimientoRepository->save($actualizado);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'requerimiento_mercurio_oficio',
            sprintf('Oficio del requerimiento «%s» adjuntado.', $req->nombre()),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->subfaseSync->sync($expediente);

        if (
            !$yaTeniaOficio
            && null !== $expediente->clienteId()
            && '' !== $expediente->clienteId()
        ) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $this->notificar->notificarRequerimientoCliente(
                    $expediente,
                    $cliente,
                    $req->nombre(),
                    $req->descripcion(),
                );
            }
        }
    }

    private function tieneTrabajoParaCliente(\App\Domain\Entity\ExpedienteRequerimientoMercurio $req): bool
    {
        foreach ($this->documentoRepository->findByRequerimientoId($req->id()) as $doc) {
            if (ResponsableRequerimientoDocumento::Cliente === $doc->responsable()) {
                return true;
            }
        }

        return [] !== $this->campoRepository->findByRequerimientoId($req->id());
    }
}
