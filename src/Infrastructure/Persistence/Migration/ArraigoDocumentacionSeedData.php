<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Migration;

/**
 * Documentación requerida fase 2 (requerimientos) para arraigos.
 *
 * @phpstan-type DocumentoSeed array{
 *     id: string,
 *     orden: int,
 *     nombre: string,
 *     descripcion: string,
 *     obligatorio: bool,
 *     tipo: string,
 *     max_imagenes: int
 * }
 */
final class ArraigoDocumentacionSeedData
{
    public const SERVICIO_ARRAIGOS_ID = 'b1000001-0001-4000-8000-000000000001';

    public const TRAMITE_SOCIAL_ID = 'c1000001-0001-4000-8000-000000000001';
    public const TRAMITE_FAMILIAR_ID = 'c1000001-0001-4000-8000-000000000002';
    public const TRAMITE_SOCIOLABORAL_ID = 'c1000001-0001-4000-8000-000000000003';
    public const TRAMITE_SOCIOFORMATIVO_ID = 'c1000001-0001-4000-8000-000000000004';

    /**
     * @return list<DocumentoSeed>
     */
    public static function documentosServicio(): array
    {
        return [
            self::doc(
                'd1000001-0001-4000-8000-000000000001',
                1,
                'Pasaporte completo',
                'PDF único con todas las páginas del pasaporte, desde la portada hasta la contraportada, incluyendo las hojas en blanco. Debe estar en vigor.',
            ),
            self::doc(
                'd1000001-0001-4000-8000-000000000002',
                2,
                'Certificado de antecedentes penales',
                'PDF único que contenga el certificado de su país de origen, la apostilla de La Haya (o legalización diplomática) y la traducción jurada si no está en castellano.',
            ),
        ];
    }

    /**
     * @return array<string, list<DocumentoSeed>>
     */
    public static function documentosPorTramite(): array
    {
        return [
            self::TRAMITE_SOCIAL_ID => [
                self::doc(
                    'd1000001-0001-4000-8000-000000000101',
                    1,
                    'Permanencia (3 años)',
                    'Histórico de empadronamiento. Si hay interrupciones, incluya en el mismo PDF facturas nominativas, historial médico o envíos de dinero que acrediten estancias fuera de España de no más de 90 días.',
                ),
                self::doc(
                    'd1000001-0001-4000-8000-000000000102',
                    2,
                    'Integración',
                    'Informe de Inserción Social emitido por su Comunidad Autónoma o Ayuntamiento, o —si tiene vínculo familiar directo con un residente legal— el acta de matrimonio o nacimiento que lo acredite.',
                ),
                self::doc(
                    'd1000001-0001-4000-8000-000000000103',
                    3,
                    'Solvencia económica',
                    'Opción A (contrato): contrato de trabajo firmado por usted y el empleador, junto con la documentación fiscal de la empresa (NIF, escrituras, últimos IVAs o Impuesto de Sociedades). Opción B (medios propios): extractos bancarios y certificados que demuestren fondos equivalentes al 200% del IPREM. Aporte un bloque u otro en un único PDF.',
                ),
            ],
            self::TRAMITE_FAMILIAR_ID => [
                self::doc(
                    'd1000001-0001-4000-8000-000000000201',
                    1,
                    'Vínculo familiar',
                    'Certificado de nacimiento del hijo español o comunitario, o acta de matrimonio o pareja de hecho inscrita con el ciudadano español.',
                ),
                self::doc(
                    'd1000001-0001-4000-8000-000000000202',
                    2,
                    'DNI del familiar español',
                    'Copia del DNI por ambas caras del ciudadano español que le concede el derecho al arraigo.',
                    true,
                    'conjunto',
                    2,
                ),
            ],
            self::TRAMITE_SOCIOLABORAL_ID => [
                self::doc(
                    'd1000001-0001-4000-8000-000000000301',
                    1,
                    'Permanencia (2 años)',
                    'Certificado de empadronamiento histórico y pruebas de apoyo para acreditar los dos años de estancia mínima.',
                ),
                self::doc(
                    'd1000001-0001-4000-8000-000000000302',
                    2,
                    'Relación laboral',
                    'Resolución judicial, acta de la Inspección de Trabajo que demuestre empleo irregular de al menos 6 meses, o Vida Laboral si tuvo una relación laboral regular previa (por ejemplo, tarjeta roja de asilo antes de la denegación).',
                ),
            ],
            self::TRAMITE_SOCIOFORMATIVO_ID => [
                self::doc(
                    'd1000001-0001-4000-8000-000000000401',
                    1,
                    'Permanencia (2 años)',
                    'Certificado de empadronamiento histórico de los últimos dos años continuados.',
                ),
                self::doc(
                    'd1000001-0001-4000-8000-000000000402',
                    2,
                    'Compromiso formativo',
                    'Declaración Responsable firmada comprometiéndose a matricularse en un curso u oferta formativa oficial una vez se le apruebe la residencia. La matrícula real se aportará en los 3 meses posteriores a la concesión.',
                ),
            ],
        ];
    }

    /**
     * @return DocumentoSeed
     */
    private static function doc(
        string $id,
        int $orden,
        string $nombre,
        string $descripcion,
        bool $obligatorio = true,
        string $tipo = 'individual',
        int $maxImagenes = 1,
    ): array {
        return [
            'id' => $id,
            'orden' => $orden,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'obligatorio' => $obligatorio,
            'tipo' => $tipo,
            'max_imagenes' => $maxImagenes,
        ];
    }
}
