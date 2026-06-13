<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum EstadoDocumentoEntregado: string
{
    case Pendiente = 'pendiente';
    case Entregado = 'entregado';
    case Validado = 'validado';
    case Rechazado = 'rechazado';
}
