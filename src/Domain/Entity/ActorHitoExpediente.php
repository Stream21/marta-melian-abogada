<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum ActorHitoExpediente: string
{
    case Cliente = 'cliente';
    case Abogado = 'abogado';
    case Sistema = 'sistema';
}
