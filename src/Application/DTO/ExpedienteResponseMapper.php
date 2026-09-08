<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;

final class ExpedienteResponseMapper
{
    /**
     * @param array{contratacion: int, requerimientos: int, total: int}|null $avisos
     * @param array<string, mixed>|null $subfaseContratacion
     * @param array<string, mixed>|null $subfaseRequerimientos
     */
    public static function fromDomain(
        Expediente $expediente,
        ?string $frontendBaseUrl = null,
        ?array $avisos = null,
        ?array $subfaseContratacion = null,
        ?array $subfaseRequerimientos = null,
        ?Cliente $cliente = null,
    ): ExpedienteResponse {
        $accessUrl = null;
        if (null !== $expediente->accessToken() && null !== $frontendBaseUrl) {
            $accessUrl = rtrim($frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        }

        $contratacion = null !== $avisos ? ($avisos['contratacion'] ?? 0) : 0;
        $requerimientos = null !== $avisos ? ($avisos['requerimientos'] ?? 0) : 0;
        $total = null !== $avisos ? ($avisos['total'] ?? 0) : 0;

        $clientName = $expediente->clientName();
        if (Cliente::NOMBRE_PROVISIONAL === $clientName) {
            // Datos antiguos: no mostrar el placeholder en listados/detalle.
            $clientName = null !== $cliente && '' !== trim($cliente->telefono())
                ? $cliente->telefono()
                : '';
        }

        $fichaDisponible = null !== $cliente
            ? !$cliente->esProvisional()
            : (null !== $expediente->clienteId()
                && '' !== $expediente->clienteId()
                && '' !== $clientName
                && Cliente::NOMBRE_PROVISIONAL !== $expediente->clientName()
                && !self::pareceTelefonoContacto($clientName));

        return new ExpedienteResponse(
            id: $expediente->id()->value(),
            numero: $expediente->numero(),
            titulo: $expediente->titulo(),
            estado: $expediente->estado()->value,
            estadoLabel: $expediente->estado()->label(),
            fechaApertura: $expediente->fechaApertura()->format(\DateTimeInterface::ATOM),
            clientName: $clientName,
            caseReference: $expediente->caseReference(),
            folderPath: $expediente->folderPath(),
            paymentStatus: $expediente->paymentStatus(),
            clienteId: $expediente->clienteId(),
            clienteFichaDisponible: $fichaDisponible,
            tramiteId: $expediente->tramiteId(),
            servicioId: $expediente->servicioId(),
            faseNegocio: $expediente->faseNegocio()->value,
            estadoFase: $expediente->estadoFase()->value,
            subfaseTramitacion: $expediente->subfaseTramitacion()?->value,
            subfaseTramitacionLabel: $expediente->subfaseTramitacion()?->label(),
            actorBandejaTramitacion: $expediente->subfaseTramitacion()?->actorBandeja(),
            honorariosAcordados: $expediente->honorariosAcordados(),
            metodoPago: $expediente->metodoPago()->value,
            planPago: $expediente->planPago()->value,
            numCuotas: $expediente->numCuotas(),
            accessUrl: $accessUrl,
            avisosPendientes: $total,
            avisosDetalle: [
                'contratacion' => $contratacion,
                'requerimientos' => $requerimientos,
            ],
            subfaseContratacion: $subfaseContratacion,
            subfaseRequerimientos: $subfaseRequerimientos,
        );
    }

    /** Etiqueta de contacto usada en altas provisionales (solo dígitos / símbolos de teléfono). */
    private static function pareceTelefonoContacto(string $valor): bool
    {
        $trimmed = trim($valor);
        if ('' === $trimmed) {
            return false;
        }
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        return strlen($digits) >= 9
            && strlen($digits) <= 15
            && 1 === preg_match('/^[\d\s+\-().]+$/u', $trimmed);
    }
}
