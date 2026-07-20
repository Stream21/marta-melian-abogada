<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum ActorResponsableDocumento: string
{
    case Cliente = 'cliente';
    case Abogado = 'abogado';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::Cliente;
    }
}
