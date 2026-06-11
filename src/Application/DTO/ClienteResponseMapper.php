<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Entity\Cliente;

final class ClienteResponseMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function fromDomain(Cliente $cliente, int $numExpedientes = 0): array
    {
        return [
            'id' => $cliente->id()->value(),
            'nombre' => $cliente->nombre(),
            'nacionalidad' => $cliente->nacionalidad(),
            'tipoDocumento' => $cliente->tipoDocumento(),
            'numDocumento' => $cliente->numDocumento(),
            'fechaNacimiento' => $cliente->fechaNacimiento()?->format('Y-m-d'),
            'lugarNacimiento' => $cliente->lugarNacimiento(),
            'estadoCivil' => $cliente->estadoCivil(),
            'domicilio' => $cliente->domicilio(),
            'codigoPostal' => $cliente->codigoPostal(),
            'ciudad' => $cliente->ciudad(),
            'provincia' => $cliente->provincia(),
            'nombrePadre' => $cliente->nombrePadre(),
            'nombreMadre' => $cliente->nombreMadre(),
            'telefono' => $cliente->telefono(),
            'email' => $cliente->email(),
            'holdedContactId' => $cliente->holdedContactId(),
            'holdedEstado' => $cliente->holdedEstado()->value,
            'holdedEstadoLabel' => $cliente->holdedEstado()->label(),
            'holdedSyncedAt' => $cliente->holdedSyncedAt()?->format(\DateTimeInterface::ATOM),
            'holdedSyncError' => $cliente->holdedSyncError(),
            'numExpedientes' => $numExpedientes,
            'documentoIdentidad' => [
                'tipoEscaneo' => $cliente->documentoIdentidadTipo()?->value,
                'tipoEscaneoLabel' => $cliente->documentoIdentidadTipo()?->label(),
                'tieneAnverso' => $cliente->tieneDocumentoIdentidad(),
                'tieneReverso' => null !== $cliente->documentoIdentidadReversoPath()
                    && '' !== $cliente->documentoIdentidadReversoPath(),
                'escaneadoAt' => $cliente->documentoIdentidadEscaneadoAt()?->format(\DateTimeInterface::ATOM),
            ],
        ];
    }
}
