<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\RequerimientoMercurioPayloadBuilder;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PlataformaTramitacion;
use App\Domain\Entity\SubfaseTramitacion;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteId;

final class ObtenerTramitacionExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedientePresentacionTelematicaRepositoryInterface $presentacionRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private TramitacionSubfaseSyncService $subfaseSync,
        private RequerimientoMercurioPayloadBuilder $requerimientoPayload,
        private string $frontendBaseUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $expedienteId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        $expediente = $this->subfaseSync->sync($expediente);

        $plataforma = PlataformaTramitacion::Mercurio->value;
        $plataformaLabel = PlataformaTramitacion::Mercurio->label();
        if (null !== $expediente->tramiteId() && '' !== $expediente->tramiteId()) {
            $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
            if (null !== $tramite) {
                $plataforma = $tramite->plataforma()->value;
                $plataformaLabel = $tramite->plataforma()->label();
            }
        }

        $subfase = $expediente->subfaseTramitacion();
        $presentacion = $this->presentacionRepository->findByExpediente($id);

        $requerimientos = [];
        foreach ($this->requerimientoRepository->findByExpediente($id) as $req) {
            $requerimientos[] = $this->requerimientoPayload->buildRequerimiento($req, false);
        }

        return [
            'expedienteId' => $expediente->id()->value(),
            'numero' => $expediente->numero(),
            'faseNegocio' => $expediente->faseNegocio()->value,
            'subfase' => $subfase?->value,
            'subfaseLabel' => $subfase?->label(),
            'actorBandeja' => $subfase?->actorBandeja() ?? 'despacho',
            'actorBandejaLabel' => $subfase?->actorBandejaLabel() ?? 'En despacho',
            'plataforma' => $plataforma,
            'plataformaLabel' => $plataformaLabel,
            'flujoSoportado' => PlataformaTramitacion::Mercurio->value === $plataforma,
            'presentacion' => null === $presentacion ? null : [
                'id' => $presentacion->id()->value(),
                'fechaPresentacion' => $presentacion->fechaPresentacion()->format('Y-m-d'),
                'numeroExpedienteExtranjeria' => $presentacion->numeroExpedienteExtranjeria(),
                'tienePresentacion' => true,
                'tieneJustificante' => true,
            ],
            'requerimientos' => $requerimientos,
            'puedeAvanzarResolucion' => null !== $subfase
                && SubfaseTramitacion::Tramitado === $subfase
                && 0 === $this->requerimientoRepository->countAbiertosByExpediente($id)
                && null !== $presentacion
                && null !== $presentacion->numeroExpedienteExtranjeria(),
            'accessUrl' => null !== $expediente->accessToken()
                ? rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken()
                : null,
        ];
    }
}
