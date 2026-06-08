<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\AreaJuridicaId;

/**
 * Identificadores fijos de las áreas jurídicas (tabla area_juridica).
 * Deben coincidir con la migración y el enum del frontend.
 */
final class AreaJuridicaCatalog
{
    public const EXTRANJERIA_NACIONALIDAD = 'a1000001-0001-4000-8000-000000000001';
    public const FAMILIA_SUCESIONES = 'a1000001-0001-4000-8000-000000000002';
    public const CIVIL_CONTRATACION = 'a1000001-0001-4000-8000-000000000003';
    public const PENAL = 'a1000001-0001-4000-8000-000000000004';
    public const LABORAL_SEGURIDAD_SOCIAL = 'a1000001-0001-4000-8000-000000000005';

    /**
     * @return list<array{id: string, codigo: string, nombre: string}>
     */
    public static function definiciones(): array
    {
        return [
            [
                'id' => self::EXTRANJERIA_NACIONALIDAD,
                'codigo' => TipoServicio::ExtranjeriaNacionalidad->value,
                'nombre' => TipoServicio::ExtranjeriaNacionalidad->label(),
            ],
            [
                'id' => self::FAMILIA_SUCESIONES,
                'codigo' => TipoServicio::FamiliaSucesiones->value,
                'nombre' => TipoServicio::FamiliaSucesiones->label(),
            ],
            [
                'id' => self::CIVIL_CONTRATACION,
                'codigo' => TipoServicio::CivilContratacion->value,
                'nombre' => TipoServicio::CivilContratacion->label(),
            ],
            [
                'id' => self::PENAL,
                'codigo' => TipoServicio::Penal->value,
                'nombre' => TipoServicio::Penal->label(),
            ],
            [
                'id' => self::LABORAL_SEGURIDAD_SOCIAL,
                'codigo' => TipoServicio::LaboralSeguridadSocial->value,
                'nombre' => TipoServicio::LaboralSeguridadSocial->label(),
            ],
        ];
    }

    public static function idFromCodigo(string $codigo): AreaJuridicaId
    {
        $tipo = TipoServicio::fromString($codigo);

        return new AreaJuridicaId(match ($tipo) {
            TipoServicio::ExtranjeriaNacionalidad => self::EXTRANJERIA_NACIONALIDAD,
            TipoServicio::FamiliaSucesiones => self::FAMILIA_SUCESIONES,
            TipoServicio::CivilContratacion => self::CIVIL_CONTRATACION,
            TipoServicio::Penal => self::PENAL,
            TipoServicio::LaboralSeguridadSocial => self::LABORAL_SEGURIDAD_SOCIAL,
        });
    }
}
