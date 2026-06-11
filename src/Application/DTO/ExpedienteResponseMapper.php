<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Entity\Expediente;

final class ExpedienteResponseMapper
{
    public static function fromDomain(Expediente $expediente, ?string $frontendBaseUrl = null): ExpedienteResponse
    {
        $accessUrl = null;
        if (null !== $expediente->accessToken() && null !== $frontendBaseUrl) {
            $accessUrl = rtrim($frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        }

        return new ExpedienteResponse(
            id: $expediente->id()->value(),
            numero: $expediente->numero(),
            titulo: $expediente->titulo(),
            estado: $expediente->estado()->value,
            fechaApertura: $expediente->fechaApertura()->format(\DateTimeInterface::ATOM),
            clientName: $expediente->clientName(),
            caseReference: $expediente->caseReference(),
            folderPath: $expediente->folderPath(),
            paymentStatus: $expediente->paymentStatus(),
            clienteId: $expediente->clienteId(),
            tramiteId: $expediente->tramiteId(),
            servicioId: $expediente->servicioId(),
            faseNegocio: $expediente->faseNegocio()->value,
            estadoFase: $expediente->estadoFase()->value,
            honorariosAcordados: $expediente->honorariosAcordados(),
            metodoPago: $expediente->metodoPago()->value,
            planPago: $expediente->planPago()->value,
            numCuotas: $expediente->numCuotas(),
            accessUrl: $accessUrl,
        );
    }
}
