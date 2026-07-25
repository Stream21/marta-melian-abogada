<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\SubfaseTramitacion;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;

/**
 * Recalcula la subfase de tramitación según presentación, seguimiento y requerimientos abiertos.
 */
final class TramitacionSubfaseSyncService
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedientePresentacionTelematicaRepositoryInterface $presentacionRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
    ) {
    }

    public function sync(Expediente $expediente, bool $marcarListoResolucion = false): Expediente
    {
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            return $expediente;
        }

        $presentacion = $this->presentacionRepository->findByExpediente($expediente->id());
        $abiertos = $this->requerimientoRepository->countAbiertosByExpediente($expediente->id());

        if ($abiertos > 0) {
            $subfase = SubfaseTramitacion::RequerimientoAbierto;
        } elseif (null === $presentacion) {
            $subfase = SubfaseTramitacion::PreparacionPresentacion;
        } elseif (null === $presentacion->numeroExpedienteExtranjeria() || '' === $presentacion->numeroExpedienteExtranjeria()) {
            $subfase = SubfaseTramitacion::PendienteRecepcion;
        } elseif ($marcarListoResolucion || SubfaseTramitacion::ListoResolucion === $expediente->subfaseTramitacion()) {
            $subfase = SubfaseTramitacion::ListoResolucion;
        } else {
            $subfase = SubfaseTramitacion::EnSeguimiento;
        }

        if ($expediente->subfaseTramitacion() === $subfase) {
            return $expediente;
        }

        $actualizado = $expediente->withSubfaseTramitacion($subfase)->touchEstadoCambio();
        $this->expedienteRepository->save($actualizado);

        return $actualizado;
    }
}
