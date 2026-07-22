<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'nacionalidad')]
#[ORM\UniqueConstraint(name: 'uniq_nacionalidad_codigo', columns: ['codigo'])]
class NacionalidadOrm
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $id;

    #[ORM\Column(type: Types::STRING, length: 3)]
    private string $codigo;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $nombre;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $activo = true;

    public function getId(): string
    {
        return $this->id;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }
}
