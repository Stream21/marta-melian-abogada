<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Entity\Cliente;

final class ClienteInputMapper
{
    public static function fromCliente(Cliente $cliente): ClienteInput
    {
        return new ClienteInput(
            $cliente->nombre(),
            $cliente->nacionalidad(),
            $cliente->tipoDocumento(),
            $cliente->numDocumento(),
            null !== $cliente->fechaNacimiento() ? $cliente->fechaNacimiento()->format('Y-m-d') : null,
            $cliente->lugarNacimiento(),
            $cliente->estadoCivil(),
            $cliente->domicilio(),
            $cliente->codigoPostal(),
            $cliente->ciudad(),
            $cliente->provincia(),
            $cliente->nombrePadre(),
            $cliente->nombreMadre(),
            $cliente->telefono(),
            $cliente->email(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(ClienteInput $input): array
    {
        return [
            'nombre' => $input->nombre,
            'nacionalidad' => $input->nacionalidad,
            'tipoDocumento' => $input->tipoDocumento,
            'numDocumento' => $input->numDocumento,
            'fechaNacimiento' => $input->fechaNacimiento,
            'lugarNacimiento' => $input->lugarNacimiento,
            'estadoCivil' => $input->estadoCivil,
            'domicilio' => $input->domicilio,
            'codigoPostal' => $input->codigoPostal,
            'ciudad' => $input->ciudad,
            'provincia' => $input->provincia,
            'nombrePadre' => $input->nombrePadre,
            'nombreMadre' => $input->nombreMadre,
            'telefono' => $input->telefono,
            'email' => $input->email,
        ];
    }
}
