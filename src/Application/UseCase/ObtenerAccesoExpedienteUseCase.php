<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\ContratacionAccesoPresenter;
use App\Application\Service\RequerimientosAccesoPresenter;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ServicioId;
use App\Domain\ValueObject\TramiteId;

final class ObtenerAccesoExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private ServicioRepositoryInterface $servicioRepository,
        private InicializarContratacionUseCase $inicializarContratacion,
        private InicializarRequerimientosUseCase $inicializarRequerimientos,
        private ContratacionAccesoPresenter $presenter,
        private RequerimientosAccesoPresenter $requerimientosPresenter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $token): array
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        $tramiteNombre = '';
        if (null !== $expediente->tramiteId()) {
            $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
            $tramiteNombre = $tramite?->nombre() ?? '';
        }

        $tipoServicio = null;
        $servicioId = $expediente->servicioId();
        if (null !== $servicioId && '' !== $servicioId) {
            $servicio = $this->servicioRepository->findById(new ServicioId($servicioId));
            $tipoServicio = $servicio?->tipo()->value;
        }

        if ($expediente->faseNegocio() === FaseNegocioExpediente::Contratacion) {
            ($this->inicializarContratacion)($expediente->id());
        }

        if ($expediente->faseNegocio() === FaseNegocioExpediente::Requerimientos) {
            ($this->inicializarRequerimientos)($expediente->id());
        }

        $vista = $this->presenter->present($expediente, $token);
        $requerimientos = $this->requerimientosPresenter->present($expediente);

        return [
            'expedienteNumero' => $expediente->numero(),
            'tramiteNombre' => $tramiteNombre,
            'tipoServicio' => $tipoServicio,
            'faseNegocio' => $expediente->faseNegocio()->value,
            'faseNegocioLabel' => $expediente->faseNegocio()->label(),
            'estadoFase' => $expediente->estadoFase()->value,
            'estadoFaseLabel' => $expediente->estadoFase()->label(),
            'fechaVencimientoFase' => $expediente->fechaVencimientoFase()?->format('Y-m-d'),
            'honorariosAcordados' => $expediente->honorariosAcordados(),
            'metodoPago' => $expediente->metodoPago()->value,
            'planPago' => $expediente->planPago()->value,
            'numCuotas' => $expediente->numCuotas(),
            ...$vista,
            'requerimientos' => $requerimientos,
        ];
    }
}
