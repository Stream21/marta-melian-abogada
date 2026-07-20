<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum SubidoPorDocumento: string
{
    case Cliente = 'cliente';
    case Abogado = 'abogado';
}
