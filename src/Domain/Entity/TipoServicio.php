<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum TipoServicio: string
{
    case ExtranjeriaNacionalidad = 'extranjeria_nacionalidad';
    case FamiliaSucesiones = 'familia_sucesiones';
    case CivilContratacion = 'civil_contratacion';
    case Penal = 'penal';
    case LaboralSeguridadSocial = 'laboral_seguridad_social';

    public function label(): string
    {
        return match ($this) {
            self::ExtranjeriaNacionalidad => 'Derecho de extranjería y nacionalidad',
            self::FamiliaSucesiones => 'Derecho de Familia y Sucesiones',
            self::CivilContratacion => 'Derecho Civil y Contratación',
            self::Penal => 'Derecho Penal',
            self::LaboralSeguridadSocial => 'Derecho laboral y Seguridad Social',
        };
    }

    public static function fromString(string $value): self
    {
        $normalized = trim($value);
        $tipo = self::tryFrom($normalized);
        if (null === $tipo) {
            throw new \InvalidArgumentException(
                'Área jurídica no válida. Valores permitidos: '
                . implode(', ', array_map(static fn (self $t) => $t->value, self::cases())),
            );
        }

        return $tipo;
    }

    public static function fromAreaId(\App\Domain\ValueObject\AreaJuridicaId $id): self
    {
        return match ($id->value()) {
            AreaJuridicaCatalog::EXTRANJERIA_NACIONALIDAD => self::ExtranjeriaNacionalidad,
            AreaJuridicaCatalog::FAMILIA_SUCESIONES => self::FamiliaSucesiones,
            AreaJuridicaCatalog::CIVIL_CONTRATACION => self::CivilContratacion,
            AreaJuridicaCatalog::PENAL => self::Penal,
            AreaJuridicaCatalog::LABORAL_SEGURIDAD_SOCIAL => self::LaboralSeguridadSocial,
            default => throw new \InvalidArgumentException('Área jurídica no reconocida.'),
        };
    }
}
