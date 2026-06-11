<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ClienteInput
{
    public function __construct(
        public string $nombre,
        public string $nacionalidad = '',
        public string $tipoDocumento = '',
        public string $numDocumento = '',
        public ?string $fechaNacimiento = null,
        public string $lugarNacimiento = '',
        public string $domicilio = '',
        public string $codigoPostal = '',
        public string $ciudad = '',
        public string $telefono = '',
        public string $email = '',
    ) {
    }
}
