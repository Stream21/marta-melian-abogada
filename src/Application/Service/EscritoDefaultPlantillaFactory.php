<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\TipoEscrito;
use Ramsey\Uuid\Uuid;

final class EscritoDefaultPlantillaFactory
{
    /**
     * Plantilla global por defecto. Designación y RGPD son iguales para todos los trámites
     * hasta que se guarde una personalizada en un trámite concreto.
     *
     * @return list<array<string, mixed>>
     */
    public static function createDefault(TipoEscrito $tipo): array
    {
        return match ($tipo) {
            TipoEscrito::HojaEncargo => self::hojaEncargo(),
            TipoEscrito::Designacion => self::designacion(),
            TipoEscrito::Rgpd => self::rgpd(),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function hojaEncargo(): array
    {
        return [
            self::title('HOJA DE ENCARGO PROFESIONAL', true, ['align' => 'center', 'fontSize' => 16]),
            self::text('En [[CIUDAD_DESPACHO]], a [[FECHA_ACTUAL]]', ['align' => 'center', 'fontSize' => 11]),
            self::section('REUNIDOS', EscritoHojaEncargoDefaultContent::reunidos()),
            self::section('EXPONEN', EscritoHojaEncargoDefaultContent::exponen()),
            self::section('ESTIPULACIONES', EscritoHojaEncargoDefaultContent::estipulaciones()),
            self::table(
                'DÉCIMA.- PROTECCIÓN DE DATOS PERSONALES',
                'INFORMACIÓN BÁSICA',
                'Información básica sobre Protección de Datos',
                EscritoHojaEncargoDefaultContent::proteccionDatosFilas(),
            ),
            self::text(EscritoHojaEncargoDefaultContent::cierre()),
            self::columns(2, [
                self::signature('signature_lawyer'),
                self::signature('signature_client'),
            ]),
        ];
    }

    /**
     * Basado en el documento «Designación de representación» del despacho.
     *
     * @return list<array<string, mixed>>
     */
    private static function designacion(): array
    {
        return [
            self::title('DOCUMENTO DE DESIGNACIÓN DE REPRESENTANTE', false),
            self::text('RD 240/2007 y RD 1155/2024'),
            self::text(
                'Nombre [[NOMBRE_CLIENTE]], Nacionalidad [[NACIONALIDAD_CLIENTE]]; identificado con [[TIPO_DOCUMENTO_CLIENTE]] No. '
                . '[[NUM_DOCUMENTO_CLIENTE]]; con fecha de nacimiento [[FECHA_NACIMIENTO_CLIENTE]] en [[LUGAR_NACIMIENTO_CLIENTE]]; '
                . 'domiciliado en [[DOMICILIO_CLIENTE]]; código postal [[CP_CLIENTE]]; y número móvil de contacto [[TELEFONO_CLIENTE]].',
            ),
            self::text(
                'A los efectos de los artículos 5 y 66 de la Ley 39/2015, de 1 de octubre, del Procedimiento Administrativo Común de las '
                . 'Administraciones Públicas, y de acuerdo con lo establecido en el art. 197 del Real Decreto 1155/2024, de 11 de enero; '
                . 'y en los artículos 11 y 12 del Real Decreto 240/2007, de 16 de febrero, DESIGNO a la Abogada cuyos datos constan a '
                . 'continuación como representante para que formule en mi nombre los trámites correspondientes para obtener '
                . '[[NOMBRE_TRAMITE]], presente y firme cuantos documentos sean reglamentariamente exigibles, así como a intervenir en '
                . 'cuantos trámites y diligencias requiera el procedimiento, salvo aquéllas en que sea necesaria mi comparecencia personal.',
            ),
            self::text(
                '[[NOMBRE_LETRADA]], Abogada con nº de colegiada [[NUM_COLEGIADO]] del [[COLEGIO_ABOGADOS]], con DNI [[NIF_LETRADA]], '
                . 'domicilio en [[DOMICILIO_DESPACHO]], tlf. [[TELEFONO_DESPACHO]] y email [[EMAIL_DESPACHO]]',
            ),
            self::text('En [[CIUDAD_DESPACHO]] a [[FECHA_ACTUAL]]'),
            self::columns(2, [
                self::signature('signature_client'),
                self::signature('signature_lawyer'),
            ]),
        ];
    }

    /**
     * Contrato de encargo de tratamiento (art. 28 RGPD).
     *
     * @return list<array<string, mixed>>
     */
    private static function rgpd(): array
    {
        return [
            self::title('CONTRATO DE ENCARGO DE TRATAMIENTO DE DATOS PERSONALES', false),
            self::section(
                'Objeto del encargo del tratamiento',
                'Teniendo en cuenta que las partes mantienen un acuerdo de prestación de servicios que implica el acceso a datos de carácter '
                . 'personal responsabilidad del cliente por parte del proveedor, mediante este contrato se da cumplimiento a la exigencia del '
                . 'artículo 28.3 del Reglamento General de Protección de Datos (UE 2016/679), que establece que el tratamiento de datos '
                . 'personales por parte de un encargado de tratamiento se regirá por un contrato u otro acto jurídico.' . "\n\n"
                . 'Mediante las presentes cláusulas se habilita a [[NOMBRE_LETRADA]], NIF nº [[NIF_LETRADA]], en adelante encargado de '
                . 'tratamiento, para tratar por cuenta de [[NOMBRE_CLIENTE]], [[TIPO_DOCUMENTO_CLIENTE]] nº [[NUM_DOCUMENTO_CLIENTE]], en '
                . 'adelante Responsable del Tratamiento, los datos de carácter personal necesarios para la prestación de [[NOMBRE_TRAMITE]], '
                . 'que implicará el acceso y tratamiento de datos personales.',
            ),
            self::section(
                'Identificación de la información afectada',
                'Para la ejecución de las prestaciones derivadas del cumplimiento del objeto de este encargo, el Responsable del tratamiento '
                . 'pone a disposición del Encargado del Tratamiento la información que se describe a continuación:' . "\n\n"
                . '* Datos identificativos de trabajadores, clientes, proveedores y solicitantes',
            ),
            self::section(
                'Duración',
                'La duración del presente acuerdo será la misma que la del acuerdo de prestación de servicios que lo origina.',
            ),
            self::section(
                'Obligaciones del encargado del tratamiento',
                'El encargado del tratamiento y todo su personal se obliga a:' . "\n\n"
                . '1. Utilizar los datos personales objeto de tratamiento, o los que recoja para su inclusión, sólo para la finalidad objeto de '
                . 'este encargo. En ningún caso podrá utilizar los datos para fines propios.' . "\n"
                . '2. Tratar los datos de acuerdo con las instrucciones del responsable del tratamiento. En el supuesto de que el encargado del '
                . 'tratamiento considera que alguna de las instrucciones infringe cualquier normativa relativa a la protección de datos, informará '
                . 'inmediatamente al responsable.' . "\n"
                . '3. Cuando así lo determine la normativa relativa a la protección de datos, llevar un registro de todas las categorías de '
                . 'actividades de tratamiento efectuadas por cuenta del responsable, que contenga las exigencias de la normativa de protección de '
                . 'datos vigente.' . "\n"
                . '4. No subcontratar ninguna de las prestaciones que formen parte del objeto de este contrato que comporten el tratamiento de '
                . 'datos personales, salvo los servicios auxiliares necesarios para el normal funcionamiento de los servicios del encargado.' . "\n\n"
                . 'Si fuera necesario subcontratar algún tratamiento, este hecho se deberá comunicar previamente y por escrito al responsable, '
                . 'con una antelación de 1 mes, indicando los tratamientos que se pretende subcontratar e identificando de forma clara e '
                . 'inequívoca la empresa subcontratista y sus datos de contacto.' . "\n\n"
                . '5. Mantener el deber de secreto respecto a los datos de carácter personal a los que haya tenido acceso en virtud del presente '
                . 'encargo, incluso después de que finalice su objeto.' . "\n"
                . '6. Garantizar que las personas autorizadas para tratar datos personales se comprometan, de forma expresa y por escrito, a '
                . 'respetar la confidencialidad y a cumplir las medidas de seguridad correspondientes.' . "\n"
                . '7. No comunicar los datos a terceras personas, salvo que cuente con la autorización expresa del responsable del tratamiento, '
                . 'en los supuestos legalmente admisibles.' . "\n"
                . '8. Garantizar la confidencialidad, integridad, disponibilidad y resiliencia permanentes de los sistemas y servicios de '
                . 'tratamiento.' . "\n"
                . '9. Cuando las personas afectadas ejerzan los derechos de acceso, rectificación, supresión, oposición y demás derechos '
                . 'recogidos en la normativa de protección de datos, ante el encargado del tratamiento, éste debe comunicarlo al responsable de '
                . 'forma inmediata y en ningún caso más allá del día laborable siguiente al de la recepción de la solicitud.' . "\n"
                . '10. Verificar, evaluar y valorar, de forma regular, la eficacia de las medidas técnicas y organizativas implantadas para '
                . 'garantizar la seguridad del tratamiento.' . "\n"
                . '11. Cuando lo exija el tratamiento, seudonimizar y cifrar los datos personales.' . "\n"
                . '12. Restaurar la disponibilidad y el acceso a los datos personales de forma rápida, en caso de incidente físico o técnico.' . "\n"
                . '13. Notificar al responsable del tratamiento, sin dilación indebida y en cualquier caso antes del plazo máximo de 72 horas, '
                . 'cualquier violación de la seguridad de los datos que constituya un riesgo para los derechos y las libertades de las personas '
                . 'físicas.' . "\n"
                . '14. Dar apoyo al responsable del tratamiento en la realización de las consultas previas a la autoridad de control, cuando '
                . 'proceda.' . "\n"
                . '15. Poner a disposición del responsable toda la información necesaria para demostrar el cumplimiento de sus obligaciones, así '
                . 'como para la realización de las auditorías o las inspecciones que realicen el responsable u otro auditor autorizado por él.' . "\n"
                . '16. Implantar las medidas de seguridad necesarias, en función de la naturaleza, alcance, contexto y los fines del tratamiento.' . "\n"
                . '17. Destino de los datos: Una vez finalice el presente contrato, el encargado del tratamiento, según las instrucciones del '
                . 'Responsable del Tratamiento, deberá suprimir o devolverle todos los datos personales que obren en su poder, salvo que deba '
                . 'conservarlos debidamente bloqueados mientras puedan derivarse responsabilidades de la ejecución de la prestación.',
            ),
            self::section(
                'Obligaciones del responsable del tratamiento',
                'Corresponde al responsable del tratamiento:' . "\n\n"
                . 'a) Entregar al encargado los datos necesarios para prestar el servicio y descritos en el presente contrato.' . "\n"
                . 'b) Realizar las consultas previas que corresponda.' . "\n"
                . 'c) Velar, de forma previa y durante todo el tratamiento, por el cumplimiento de la normativa vigente en materia de datos '
                . 'personales por parte del encargado.' . "\n"
                . 'd) Supervisar el tratamiento, incluida la realización de inspecciones y auditorías.',
            ),
            self::section(
                'Responsabilidades',
                'El Responsable del Tratamiento queda exonerado de cualquier responsabilidad derivada del incumplimiento por parte del Encargado '
                . 'del tratamiento de las estipulaciones contenidas en el presente contrato, que será considerado también responsable del '
                . 'tratamiento, respondiendo de las infracciones en que hubiera incurrido personalmente ante las Autoridades de Protección de '
                . 'Datos, así como de las reclamaciones civiles y penales que los afectados por el incumplimiento puedan interponer ante la '
                . 'jurisdicción ordinaria, exonerando de toda responsabilidad al Responsable del Tratamiento.',
            ),
            self::section(
                'Declaración responsable de cumplimiento del Reglamento 2016/679',
                'En cumplimiento del artículo 28.1 del Reglamento de Protección de Datos que obliga a escoger únicamente encargados de '
                . 'tratamiento que ofrezcan garantías suficientes para aplicar medidas técnicas y organizativas apropiadas, el Encargado de '
                . 'Tratamiento declara responsablemente:' . "\n\n"
                . 'a) Que dispone de un registro con las Actividades de tratamiento de datos efectuadas bajo su responsabilidad.' . "\n"
                . 'b) Que cumple, en función de su actividad desarrollada, con las obligaciones y principios impuestos por el Reglamento General '
                . 'de Protección de Datos (UE 2016/679).' . "\n"
                . 'c) Que ha realizado el correspondiente análisis de riesgos donde se determinan las medidas de seguridad técnicas y '
                . 'organizativas que debe aplicar para cumplir con el Reglamento.' . "\n"
                . 'd) Que ha adoptado las medidas de seguridad necesarias que garanticen el control físico a sus instalaciones, el acceso '
                . 'individualizado a sus sistemas, la delimitación del acceso a los datos del Responsable, las copias de seguridad, la '
                . 'custodia de documentación, la protección perimetral y antivirus, el registro de incidencias y los mecanismos de notificación '
                . 'de quiebras de seguridad.' . "\n"
                . 'e) Que se compromete a mantener el deber de secreto y garantizar que las personas autorizadas cumplan las medidas de '
                . 'seguridad.' . "\n"
                . 'f) Que proactivamente realiza controles periódicos de cumplimiento de las obligaciones y medidas de seguridad técnicas y '
                . 'organizativas que garanticen el cumplimiento del Reglamento.',
            ),
            self::section(
                'Información protección de datos',
                'Ambas partes reconocen haber sido informadas de las finalidades del tratamiento de los datos personales suministrados en el '
                . 'presente contrato y los derivados de la ejecución del mismo, de la posibilidad de remitirse información comercial en base al '
                . 'interés legítimo de ambas partes por medios electrónicos, así como de cómo ejercer sus derechos y demás obligaciones de las '
                . 'normativas de protección de datos.' . "\n\n"
                . 'El encargado de tratamiento declara responsablemente que cumple con los compromisos adquiridos en la cláusula 7 del presente '
                . 'contrato.',
            ),
            self::text('En [[CIUDAD_DESPACHO]], a [[FECHA_ACTUAL]]'),
            self::columns(2, [
                self::signature('signature_client'),
                self::signature('signature_lawyer'),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param array{align?: string, fontSize?: int} $style
     *
     * @return array<string, mixed>
     */
    private static function title(string $title, bool $showReferencia, array $style = []): array
    {
        $block = [
            'id' => Uuid::uuid4()->toString(),
            'type' => 'title',
            'title' => $title,
            'showReferencia' => $showReferencia,
        ];

        if ([] !== $style) {
            $block['style'] = $style;
        }

        return $block;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param array{align?: string, fontSize?: int} $style
     *
     * @return array<string, mixed>
     */
    private static function text(string $content, array $style = []): array
    {
        $block = [
            'id' => Uuid::uuid4()->toString(),
            'type' => 'text',
            'content' => $content,
        ];

        if ([] !== $style) {
            $block['style'] = $style;
        }

        return $block;
    }

    /**
     * @return array<string, mixed>
     */
    private static function section(string $title, string $content): array
    {
        return [
            'id' => Uuid::uuid4()->toString(),
            'type' => 'section',
            'title' => $title,
            'content' => $content,
        ];
    }

    /**
     * @param list<array{label: string, value: string}> $rows
     *
     * @return array<string, mixed>
     */
    private static function table(string $clauseTitle, string $title, string $subtitle, array $rows): array
    {
        return [
            'id' => Uuid::uuid4()->toString(),
            'type' => 'table',
            'clauseTitle' => $clauseTitle,
            'title' => $title,
            'subtitle' => $subtitle,
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param list<array<string, mixed>> $children
     *
     * @return array<string, mixed>
     */
    private static function columns(int $columnCount, array $children): array
    {
        return [
            'id' => Uuid::uuid4()->toString(),
            'type' => 'columns',
            'columnCount' => $columnCount,
            'children' => $children,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function signature(string $type, string $label = ''): array
    {
        return [
            'id' => Uuid::uuid4()->toString(),
            'type' => $type,
            'label' => $label,
        ];
    }
}
