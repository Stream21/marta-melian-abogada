<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Entity\Cliente;
use App\Domain\Entity\Expediente;

final class ExpedienteResponseMapper
{
    /**
     * @param array{contratacion: int, documentacion: int, notificaciones?: int, total: int}|null $avisos
     * @param array<string, mixed>|null $subfaseContratacion
     * @param array<string, mixed>|null $subfaseDocumentacion
     * @param array<string, mixed>|null $subfaseTramitacionDetalle
     * @param array<string, mixed>|null $resumenCobros
     * @param array{contenido: string, createdAt: string, archivada: bool}|null $ultimaNota
     */
    public static function fromDomain(
        Expediente $expediente,
        ?string $frontendBaseUrl = null,
        ?array $avisos = null,
        ?array $subfaseContratacion = null,
        ?array $subfaseDocumentacion = null,
        ?Cliente $cliente = null,
        ?array $resumenCobros = null,
        ?array $subfaseTramitacionDetalle = null,
        int $notasActivas = 0,
        ?array $ultimaNota = null,
    ): ExpedienteResponse {
        $accessUrl = null;
        if (null !== $expediente->accessToken() && null !== $frontendBaseUrl) {
            $accessUrl = rtrim($frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        }

        $contratacion = null !== $avisos ? (int) ($avisos['contratacion'] ?? 0) : 0;
        $documentacion = null !== $avisos ? (int) ($avisos['documentacion'] ?? 0) : 0;
        $notificaciones = null !== $avisos ? (int) ($avisos['notificaciones'] ?? 0) : 0;
        $total = null !== $avisos ? (int) ($avisos['total'] ?? 0) : 0;

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
            fechaVencimientoFase: $expediente->fechaVencimientoFase()?->format('Y-m-d'),
            avisosPendientes: $total,
            avisosDetalle: [
                'contratacion' => $contratacion,
                'documentacion' => $documentacion,
                'notificaciones' => $notificaciones,
            ],
            subfaseContratacion: $subfaseContratacion,
            subfaseDocumentacion: $subfaseDocumentacion,
            subfaseTramitacionDetalle: $subfaseTramitacionDetalle,
            resumenCobros: $resumenCobros,
            notasActivas: $notasActivas,
            ultimaNota: $ultimaNota,
            canalesNotificacion: $expediente->canalesNotificacion(),
            clienteTieneTelefono: null !== $cliente && '' !== trim($cliente->telefono()),
            clienteTieneEmail: null !== $cliente && '' !== trim($cliente->email()),
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
