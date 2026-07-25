<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\NotificarTramitacionClienteService;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\TipoRequerimientoMercurio;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteEscritoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class VincularEscritoRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteEscritoRepositoryInterface $escritoRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private NotificarTramitacionClienteService $notificar,
        private TramitacionSubfaseSyncService $subfaseSync,
    ) {
    }

    public function __invoke(string $expedienteId, string $requerimientoId, string $escritoId): void
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
        if (TipoRequerimientoMercurio::Escrito !== $req->tipo()) {
            throw new \InvalidArgumentException('Este requerimiento no es de tipo escrito.');
        }

        $escrito = $this->escritoRepository->findById($escritoId);
        if (null === $escrito || $escrito->expedienteId()->value() !== $id->value()) {
            throw new \InvalidArgumentException('Escrito no encontrado en este expediente.');
        }
        if ('' === trim($escrito->pdfPath())) {
            throw new \InvalidArgumentException('El escrito seleccionado no tiene PDF generado.');
        }

        $actualizado = $req->withArchivo($escrito->pdfPath(), $escrito->titulo() . '.pdf');
        $this->requerimientoRepository->save($actualizado);

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'requerimiento_mercurio_escrito',
            sprintf('Escrito «%s» vinculado al requerimiento «%s».', $escrito->titulo(), $req->nombre()),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $expedienteActualizado = $this->subfaseSync->sync($expediente);

        if (
            null !== $expediente->clienteId()
            && '' !== $expediente->clienteId()
            && 0 === $this->requerimientoRepository->countAbiertosByExpediente($id)
        ) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $this->notificar->notificarVueltaSeguimiento($expedienteActualizado, $cliente);
            }
        }
    }
}
