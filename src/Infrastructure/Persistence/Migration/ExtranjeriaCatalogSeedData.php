<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migration;

use App\Domain\Entity\AreaJuridicaCatalog;

/**
 * Catálogo de servicios y trámites de extranjería y nacionalidad.
 *
 * @phpstan-type TramiteSeed array{id: string, nombre: string, honorarios: float, plataforma: string, requiere_procurador: bool}
 * @phpstan-type ServicioSeed array{id: string, nombre: string, tramites: list<TramiteSeed>}
 */
final class ExtranjeriaCatalogSeedData
{
    public const AREA_ID = AreaJuridicaCatalog::EXTRANJERIA_NACIONALIDAD;

    /**
     * @return list<ServicioSeed>
     */
    public static function servicios(): array
    {
        return [
            [
                'id' => 'b1000001-0001-4000-8000-000000000001',
                'nombre' => 'Residencias por Circunstancias Excepcionales (Arraigos)',
                'tramites' => [
                    self::tramite('c1000001-0001-4000-8000-000000000002', 'Solicitud Inicial de Arraigo Familiar'),
                    self::tramite('c1000001-0001-4000-8000-000000000001', 'Solicitud Inicial de Arraigo Social'),
                    self::tramite('c1000001-0001-4000-8000-000000000004', 'Solicitud Inicial de Arraigo Socioformativo'),
                    self::tramite('c1000001-0001-4000-8000-000000000003', 'Solicitud Inicial de Arraigo Sociolaboral'),
                    self::tramite('c1000001-0001-4000-8000-000000000005', 'Solicitud Inicial de Arraigo de Segunda Oportunidad'),
                    self::tramite('c1000001-0001-4000-8000-000000000006', 'Solicitud de Residencia por Razones Humanitarias o Protección Internacional'),
                ],
            ],
            [
                'id' => 'b1000001-0001-4000-8000-000000000002',
                'nombre' => 'Régimen de Ciudadanos de la Unión Europea (Comunitarios)',
                'tramites' => [
                    self::tramite('c1000001-0001-4000-8000-000000000007', 'Certificado de Registro de Ciudadano de la Unión Europea'),
                    self::tramite('c1000001-0001-4000-8000-000000000008', 'Tarjeta de Familiar de Ciudadano de la Unión Europea'),
                    self::tramite('c1000001-0001-4000-8000-000000000009', 'Tarjeta de Residencia Permanente de Familiar de Ciudadano de la Unión Europea'),
                    self::tramite('c1000001-0001-4000-8000-000000000010', 'Certificado de Derecho a la Residencia Permanente para Ciudadanos de la UE'),
                ],
            ],
            [
                'id' => 'b1000001-0001-4000-8000-000000000003',
                'nombre' => 'Residencias Temporales (Régimen General)',
                'tramites' => [
                    self::tramite('c1000001-0001-4000-8000-000000000011', 'Autorización Inicial de Residencia Temporal No Lucrativa'),
                    self::tramite('c1000001-0001-4000-8000-000000000012', 'Autorización Inicial de Residencia y Trabajo por Cuenta Ajena'),
                    self::tramite('c1000001-0001-4000-8000-000000000013', 'Autorización Inicial de Residencia y Trabajo por Cuenta Propia'),
                    self::tramite('c1000001-0001-4000-8000-000000000014', 'Autorización de Residencia Temporal por Reagrupación Familiar ordinaria'),
                    self::tramite('c1000001-0001-4000-8000-000000000015', 'Autorización de Residencia con excepción a la autorización de trabajo'),
                ],
            ],
            [
                'id' => 'b1000001-0001-4000-8000-000000000004',
                'nombre' => 'Estudiantes y Movilidad Internacional',
                'tramites' => [
                    self::tramite('c1000001-0001-4000-8000-000000000016', 'Autorización de Estancia por Estudios'),
                    self::tramite('c1000001-0001-4000-8000-000000000017', 'Prórroga de Autorización de Estancia por Estudios'),
                    self::tramite('c1000001-0001-4000-8000-000000000018', 'Autorización de Residencia para Prácticas Profesionales'),
                    self::tramite('c1000001-0001-4000-8000-000000000019', 'Autorización de Residencia para Búsqueda de Empleo'),
                    self::tramite('c1000001-0001-4000-8000-000000000020', 'Autorización de Trabajo para titulares de Estancia por Estudios'),
                ],
            ],
            [
                'id' => 'b1000001-0001-4000-8000-000000000005',
                'nombre' => 'Ley de Emprendedores y Nómadas Digitales (UGE-CE)',
                'tramites' => [
                    self::tramite('c1000001-0001-4000-8000-000000000021', 'Visado / Autorización de Residencia para Nómadas Digitales'),
                    self::tramite('c1000001-0001-4000-8000-000000000022', 'Autorización de Residencia para Profesionales Altamente Cualificados'),
                    self::tramite('c1000001-0001-4000-8000-000000000023', 'Autorización de Residencia para Inversores'),
                    self::tramite('c1000001-0001-4000-8000-000000000024', 'Autorización de Residencia para Emprendedores y Actividad Empresarial Innovadora'),
                ],
            ],
            [
                'id' => 'b1000001-0001-4000-8000-000000000006',
                'nombre' => 'Residencias de Larga Duración y Menores',
                'tramites' => [
                    self::tramite('c1000001-0001-4000-8000-000000000025', 'Autorización de Residencia de Larga Duración Nacional'),
                    self::tramite('c1000001-0001-4000-8000-000000000026', 'Autorización de Residencia de Larga Duración - UE'),
                    self::tramite('c1000001-0001-4000-8000-000000000027', 'Recuperación de la titularidad de la Residencia de Larga Duración'),
                    self::tramite('c1000001-0001-4000-8000-000000000028', 'Autorización de Residencia para menores de edad nacidos en España'),
                    self::tramite('c1000001-0001-4000-8000-000000000029', 'Autorización de Residencia para menores de edad NO nacidos en España'),
                ],
            ],
            [
                'id' => 'b1000001-0001-4000-8000-000000000007',
                'nombre' => 'Renovaciones y Modificaciones de Permisos',
                'tramites' => [
                    self::tramite('c1000001-0001-4000-8000-000000000030', 'Renovación de Autorización de Residencia No Lucrativa'),
                    self::tramite('c1000001-0001-4000-8000-000000000031', 'Renovación de Autorización de Residencia y Trabajo'),
                    self::tramite('c1000001-0001-4000-8000-000000000032', 'Modificación de Circunstancias Excepcionales a Régimen General de Trabajo'),
                    self::tramite('c1000001-0001-4000-8000-000000000033', 'Modificación de Estancia por Estudios a Autorización de Residencia y Trabajo'),
                ],
            ],
            [
                'id' => 'b1000001-0001-4000-8000-000000000008',
                'nombre' => 'Ciudadanía y Nacionalidad',
                'tramites' => [
                    self::tramite('c1000001-0001-4000-8000-000000000034', 'Solicitud de Nacionalidad Española por Residencia'),
                    self::tramite('c1000001-0001-4000-8000-000000000035', 'Solicitud de Nacionalidad por Opción o Carta de Naturaleza'),
                    self::tramite('c1000001-0001-4000-8000-000000000036', 'Solicitud de dispensa de exámenes del Instituto Cervantes'),
                ],
            ],
            [
                'id' => 'b1000001-0001-4000-8000-000000000009',
                'nombre' => 'Recursos y Litigios de Extranjería',
                'tramites' => [
                    self::tramiteLexnet('c1000001-0001-4000-8000-000000000037', 'Recurso Administrativo de Reposición'),
                    self::tramiteLexnet('c1000001-0001-4000-8000-000000000038', 'Recurso de Alzada'),
                    self::tramiteLexnet('c1000001-0001-4000-8000-000000000039', 'Recurso Contencioso-Administrativo Judicial'),
                    self::tramiteLexnet('c1000001-0001-4000-8000-000000000040', 'Demanda judicial por Silencio Administrativo en Nacionalidades'),
                ],
            ],
        ];
    }

    /**
     * @return TramiteSeed
     */
    private static function tramite(string $id, string $nombre, float $honorarios = 500.0): array
    {
        return [
            'id' => $id,
            'nombre' => $nombre,
            'honorarios' => $honorarios,
            'plataforma' => 'mercurio',
            'requiere_procurador' => false,
        ];
    }

    /**
     * @return TramiteSeed
     */
    private static function tramiteLexnet(string $id, string $nombre, float $honorarios = 500.0): array
    {
        return [
            'id' => $id,
            'nombre' => $nombre,
            'honorarios' => $honorarios,
            'plataforma' => 'lexnet',
            'requiere_procurador' => true,
        ];
    }
}
