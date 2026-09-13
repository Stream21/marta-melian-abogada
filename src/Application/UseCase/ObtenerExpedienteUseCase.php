<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ExpedienteResponseMapper;
use App\Application\Service\ContratacionCompletitudValidator;
use App\Application\Service\CobrosResumenListadoService;
use App\Application\Service\DocumentacionSubfaseListadoService;
use App\Application\Service\TramitacionSubfaseListadoService;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;

final class ObtenerExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionCompletitudValidator $contratacionCompletitud,
        private string $frontendBaseUrl,
        private DocumentacionSubfaseListadoService $documentacionSubfaseListado,
        private CobrosResumenListadoService $cobrosResumenListado,
        private TramitacionSubfaseListadoService $tramitacionSubfaseListado,
    ) {
    }

    public function __invoke(string $expedienteId): \App\Application\DTO\ExpedienteResponse
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $subfaseContratacion = null;
        $subfaseDocumentacion = null;
        $subfaseTramitacionDetalle = null;
        if (FaseNegocioExpediente::Contratacion === $expediente->faseNegocio()) {
            $subfaseContratacion = $this->contratacionCompletitud->subfaseContratacionParaListado($expediente->id());
        }
        if (FaseNegocioExpediente::Documentacion === $expediente->faseNegocio()) {
            $subfaseDocumentacion = $this->documentacionSubfaseListado->paraExpediente($expediente->id());
        }
        if (FaseNegocioExpediente::Tramitacion === $expediente->faseNegocio()) {
            $subfaseTramitacionDetalle = $this->tramitacionSubfaseListado->paraExpediente($expediente->id());
        }

        $cliente = null;
        if (null !== $expediente->clienteId() && '' !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
        }

        return ExpedienteResponseMapper::fromDomain(
            $expediente,
            $this->frontendBaseUrl,
            null,
            $subfaseContratacion,
            $subfaseDocumentacion,
            $cliente,
            $this->cobrosResumenListado->paraExpediente($expediente),
            $subfaseTramitacionDetalle,
        );
    }
}
