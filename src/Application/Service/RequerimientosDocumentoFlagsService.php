<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ActorResponsableDocumento;
use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\SubidoPorDocumento;

final class RequerimientosDocumentoFlagsService
{
    public function __construct(
        private RequerimientosProgresoCalculator $progresoCalculator,
    ) {
    }

    /**
     * @param list<array{id: string, nombre: string, orden: int}> $archivos
     *
     * @return array<string, mixed>
     */
    public function flags(
        ExpedienteDocumentoRequerido $doc,
        ?ExpedienteDocumentoEntregado $entrega,
        array $archivos,
    ): array {
        $estado = $entrega?->estado() ?? EstadoDocumentoEntregado::Pendiente;
        $subidoPor = $entrega?->subidoPor() ?? SubidoPorDocumento::Cliente;
        $responsableActual = $entrega?->responsableActual() ?? ActorResponsableDocumento::Cliente;
        $esEntregaCliente = SubidoPorDocumento::Cliente === $subidoPor;
        $tieneArchivo = [] !== $archivos || (null !== $entrega && '' !== trim($entrega->archivoPath()));
        $parcialConArchivos = $tieneArchivo && EstadoDocumentoEntregado::Pendiente === $estado;

        $puedeSubirCliente = ActorResponsableDocumento::Cliente === $responsableActual
            && in_array($estado, [
                EstadoDocumentoEntregado::Pendiente,
                EstadoDocumentoEntregado::Rechazado,
            ], true);

        return [
            'estado' => $estado->value,
            'estadoLabel' => $this->progresoCalculator->estadoLabel($estado, $subidoPor, $responsableActual),
            'entregadoAt' => $entrega?->entregadoAt()->format(\DateTimeInterface::ATOM),
            'notaRechazo' => $entrega?->notaRechazo(),
            'tieneArchivo' => $tieneArchivo,
            'archivos' => $archivos,
            'subidoPor' => $subidoPor->value,
            'responsableActual' => $responsableActual->value,
            'requiereRevision' => EstadoDocumentoEntregado::Entregado === $estado && $esEntregaCliente,
            'puedeValidarAbogado' => EstadoDocumentoEntregado::Entregado === $estado && $esEntregaCliente,
            'puedeDevolverAbogado' => (EstadoDocumentoEntregado::Entregado === $estado && $esEntregaCliente)
                || (EstadoDocumentoEntregado::Validado === $estado && $esEntregaCliente),
            'puedeSubirAbogado' => (
                in_array($estado, [
                    EstadoDocumentoEntregado::Pendiente,
                    EstadoDocumentoEntregado::Rechazado,
                ], true)
                && (
                    null === $entrega
                    || ActorResponsableDocumento::Abogado === $responsableActual
                )
            ) || (
                EstadoDocumentoEntregado::Validado === $estado
                && SubidoPorDocumento::Abogado === $subidoPor
            ),
            'puedeSubir' => $puedeSubirCliente,
            'puedeTomarAbogado' => in_array($estado, [
                EstadoDocumentoEntregado::Pendiente,
                EstadoDocumentoEntregado::Rechazado,
            ], true)
                && ActorResponsableDocumento::Cliente === $responsableActual
                && !$parcialConArchivos,
            'puedeDerivarCliente' => null !== $entrega && (
                (
                    ActorResponsableDocumento::Abogado === $responsableActual
                    && EstadoDocumentoEntregado::Validado !== $estado
                ) || (
                    EstadoDocumentoEntregado::Validado === $estado
                    && SubidoPorDocumento::Abogado === $subidoPor
                    && \App\Domain\Entity\TipoDocumentoRequerido::Conjunto === $doc->tipo()
                )
            ),
            'parcialConArchivos' => $parcialConArchivos,
        ];
    }

    /**
     * @param list<array<string, mixed>> $documentos
     *
     * @return array{esperandoAbogado: bool, agenteResponsableExpediente: string|null}
     */
    public function resumenExpediente(array $documentos): array
    {
        $esperandoAbogado = false;
        $pendienteCliente = false;

        foreach ($documentos as $documento) {
            if (($documento['estado'] ?? '') === EstadoDocumentoEntregado::Entregado->value) {
                $esperandoAbogado = true;
            }

            if (
                ($documento['responsableActual'] ?? '') === ActorResponsableDocumento::Cliente->value
                && in_array($documento['estado'] ?? '', [
                    EstadoDocumentoEntregado::Pendiente->value,
                    EstadoDocumentoEntregado::Rechazado->value,
                ], true)
            ) {
                $pendienteCliente = true;
            }
        }

        $agenteResponsableExpediente = null;
        if ($esperandoAbogado) {
            $agenteResponsableExpediente = ActorResponsableDocumento::Abogado->value;
        } elseif ($pendienteCliente) {
            $agenteResponsableExpediente = ActorResponsableDocumento::Cliente->value;
        }

        return [
            'esperandoAbogado' => $esperandoAbogado,
            'agenteResponsableExpediente' => $agenteResponsableExpediente,
        ];
    }
}
