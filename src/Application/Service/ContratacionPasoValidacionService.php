<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ContratacionPaso;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PasoContratacionCliente;

final class ContratacionPasoValidacionService
{
    /**
     * @param ContratacionPaso[] $pasos
     */
    public function requiereValidacionAbogado(
        ContratacionPaso $paso,
        Expediente $expediente,
        array $pasos,
    ): bool {
        if (EstadoPasoContratacion::RealizadoCliente === $paso->estado()) {
            return true;
        }

        if (PasoContratacionCliente::Pago !== $paso->paso()
            || MetodoPagoExpediente::Manual !== $expediente->metodoPago()
            || EstadoPasoContratacion::Pendiente !== $paso->estado()) {
            return false;
        }

        foreach ($pasos as $otroPaso) {
            if (PasoContratacionCliente::Firmas === $otroPaso->paso()) {
                return EstadoPasoContratacion::ValidadoAbogado === $otroPaso->estado();
            }
        }

        return false;
    }

    /**
     * @param ContratacionPaso[] $pasos
     */
    public function countPasosPendientesRevision(Expediente $expediente, array $pasos): int
    {
        $count = 0;
        foreach ($pasos as $paso) {
            if ($this->requiereValidacionAbogado($paso, $expediente, $pasos)) {
                ++$count;
            }
        }

        return $count;
    }
}
