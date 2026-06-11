<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class ActualizarDespachoConfigInput
{
    public function __construct(
        public string $nombreFirma,
        public string $nombreLetrada,
        public string $numColegiado,
        public string $direccion,
        public string $ciudad,
        public string $subtituloProfesional = '',
        public string $telefono = '',
        public string $email = '',
        public string $web = '',
        public string $nif = '',
        public string $colegioAbogados = '',
        public string $iban = '',
        public string $entidadBancaria = '',
        public string $titularCuenta = '',
        public ?string $cabeceraHtml = null,
        public ?string $pieHtml = null,
    ) {
    }
}
