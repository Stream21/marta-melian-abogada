<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\GestionPostResolucion;
use App\Domain\Entity\OutcomeResolucion;
use App\Domain\Entity\PlataformaTramitacion;
use App\Domain\Entity\Tramite;

/**
 * Packs de gestiones post-resolución según el tipo de trámite.
 */
final class GestionesPostResolucionFactory
{
    /** Portal ICPPlus TIE (cita previa Policía). */
    private const URL_CITA = 'https://sede.administracionespublicas.gob.es/icpplustiej/icpplus/citar?locale=es';

    /** Impreso tasa 790 código 012 (Policía Nacional). */
    private const URL_TASA = 'https://sede.policia.gob.es/Tasa790_012/ImpresoRellenar';

    /** Listado oficial de modelos (EX-17 y resto). */
    private const URL_EX17 = 'https://www.inclusion.gob.es/web/migraciones/modelos-generales';

    /** Información Policía: documentación para TIE inicial/renovación. */
    private const URL_INFO_TIE = 'https://sede.policia.gob.es/portalCiudadano/_es/tramites_extranjeria_tramite_tarjeta_residencia_residenciaytrabajo.php';

    /**
     * Corrige URLs antiguas/rotas ya persistidas en gestiones_json.
     */
    public static function normalizeUrl(?string $url): ?string
    {
        if (null === $url || '' === trim($url)) {
            return null;
        }

        $url = trim($url);

        if (
            str_contains($url, 'sede.administracionespublicas.gob.es/icpplus')
            && !str_contains($url, 'icpplustiej')
        ) {
            return self::URL_CITA;
        }

        if (
            str_contains($url, '2157917/17-Formulario_TIE.pdf')
            || str_contains($url, 'documents/410169/2157917/')
        ) {
            return self::URL_EX17;
        }

        return $url;
    }

    /**
     * @param list<GestionPostResolucion> $gestiones
     *
     * @return list<array{id: string, titulo: string, descripcion: string, url: ?string, hecho: bool, orden: int}>
     */
    public static function toResponseArray(array $gestiones): array
    {
        return array_map(
            static function (GestionPostResolucion $g): array {
                $data = $g->toArray();
                $data['url'] = self::normalizeUrl($data['url'] ?? null);

                return $data;
            },
            $gestiones,
        );
    }

    /**
     * @return list<GestionPostResolucion>
     */
    public function crearPara(OutcomeResolucion $outcome, ?Tramite $tramite): array
    {
        if (OutcomeResolucion::Denegada === $outcome) {
            return [
                new GestionPostResolucion(
                    'revisar_plazos_recurso',
                    'Revisar plazos de recurso',
                    'Consulte con su abogado los plazos para reposición, alzada o recurso contencioso según el caso.',
                    null,
                    false,
                    1,
                ),
            ];
        }

        if (null === $tramite) {
            return $this->packTie();
        }

        if (PlataformaTramitacion::LexNet === $tramite->plataforma()) {
            return [
                new GestionPostResolucion(
                    'seguimiento_despacho',
                    'Seguimiento con el despacho',
                    'Su abogado le indicará los siguientes pasos del procedimiento judicial o administrativo.',
                    null,
                    false,
                    1,
                ),
            ];
        }

        $nombre = mb_strtolower($tramite->nombre());

        if (str_contains($nombre, 'nacionalidad') || str_contains($nombre, 'dispensa')) {
            return $this->packNacionalidad();
        }

        if (
            str_contains($nombre, 'ciudadano de la unión')
            || str_contains($nombre, 'familiar de ciudadano')
            || str_contains($nombre, 'comunidad')
            || str_contains($nombre, 'certificado de registro')
            || str_contains($nombre, 'certificado de derecho')
        ) {
            return $this->packComunitario($nombre);
        }

        return $this->packTie();
    }

    /**
     * @return list<GestionPostResolucion>
     */
    private function packTie(): array
    {
        return [
            new GestionPostResolucion(
                'tasa_790_012',
                'Pagar tasa modelo 790 código 012',
                'Genere e imprima el impreso y abónelo en entidad bancaria antes de la cita de huellas.',
                self::URL_TASA,
                false,
                1,
            ),
            new GestionPostResolucion(
                'modelo_ex17',
                'Cumplimentar modelo EX-17',
                'Formulario de solicitud de Tarjeta de Identidad de Extranjero (TIE), firmado.',
                self::URL_EX17,
                false,
                2,
            ),
            new GestionPostResolucion(
                'cita_huellas',
                'Pedir cita Policía — toma de huellas / TIE',
                'En la sede electrónica elija el trámite de expedición de TIE (huellas) en su provincia.',
                self::URL_CITA,
                false,
                3,
            ),
            new GestionPostResolucion(
                'acudir_huellas',
                'Acudir a la cita con la documentación',
                'Lleve resolución, pasaporte, EX-17, justificante de tasa, foto carnet y empadronamiento si lo piden.',
                self::URL_INFO_TIE,
                false,
                4,
            ),
            new GestionPostResolucion(
                'recogida_tie',
                'Cita de recogida de la TIE',
                'Cuando le indiquen, pida cita para recoger la tarjeta física.',
                self::URL_CITA,
                false,
                5,
            ),
        ];
    }

    /**
     * @return list<GestionPostResolucion>
     */
    private function packComunitario(string $nombre): array
    {
        $modelo = str_contains($nombre, 'familiar') ? 'EX-19' : 'EX-18';

        return [
            new GestionPostResolucion(
                'modelo_ue',
                'Cumplimentar modelo ' . $modelo,
                'Formulario correspondiente al certificado o tarjeta de familiar de ciudadano de la UE.',
                self::URL_EX17,
                false,
                1,
            ),
            new GestionPostResolucion(
                'cita_policia_ue',
                'Pedir cita en Policía',
                'Solicite cita previa para el certificado de registro o la tarjeta de familiar.',
                self::URL_CITA,
                false,
                2,
            ),
            new GestionPostResolucion(
                'tasa_si_aplica',
                'Tasa 790-012 (si aplica)',
                'En tarjetas de familiar suele requerirse el pago de la tasa. Consulte con su abogado.',
                self::URL_TASA,
                false,
                3,
            ),
        ];
    }

    /**
     * @return list<GestionPostResolucion>
     */
    private function packNacionalidad(): array
    {
        return [
            new GestionPostResolucion(
                'jura_nacionalidad',
                'Jura o promesa de nacionalidad',
                'Acuda al Registro Civil o notario según le indiquen para la jura/promesa.',
                null,
                false,
                1,
            ),
            new GestionPostResolucion(
                'dni_pasaporte',
                'Solicitar DNI y pasaporte español',
                'Tras la jura, gestione el DNI y el pasaporte español en Policía / cita previa.',
                self::URL_CITA,
                false,
                2,
            ),
        ];
    }
}
