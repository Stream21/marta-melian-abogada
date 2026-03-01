<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum EstadoExpediente: string
{
    case Abierto = 'abierto';
    case Cerrado = 'cerrado';
    case Archivado = 'archivado';
}
