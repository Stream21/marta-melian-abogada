<?php

declare(strict_types=1);

namespace App\Application\Service;

/**
 * Contenido por defecto de la Hoja de Encargo Profesional.
 * Basado en el documento firmado del despacho Marta Melián Guerra.
 */
final class EscritoHojaEncargoDefaultContent
{
    public static function reunidos(): string
    {
        return 'De una parte, [[NOMBRE_LETRADA]], con DNI [[NIF_LETRADA]], abogada colegiada en el [[COLEGIO_ABOGADOS]], '
            . 'con número de colegiado [[NUM_COLEGIADO]], con domicilio profesional en [[DOMICILIO_DESPACHO]], '
            . 'teléfono [[TELEFONO_DESPACHO]] y dirección de correo electrónico [[EMAIL_DESPACHO]] '
            . '(de ahora en adelante, la ABOGADA).' . "\n\n"
            . 'Y de otra, [[NOMBRE_CLIENTE]], Nacionalidad [[NACIONALIDAD_CLIENTE]]; identificada con [[TIPO_DOCUMENTO_CLIENTE]] No. '
            . '[[NUM_DOCUMENTO_CLIENTE]]; con fecha de nacimiento [[FECHA_NACIMIENTO_CLIENTE]] en [[LUGAR_NACIMIENTO_CLIENTE]]; '
            . 'domiciliada en [[DOMICILIO_CLIENTE]]; código postal [[CP_CLIENTE]]; y número móvil de contacto [[TELEFONO_CLIENTE]] '
            . '(de ahora en adelante, el CLIENTE).';
    }

    public static function exponen(): string
    {
        return 'Que las partes se reconocen mutuamente la capacidad legal suficiente para otorgar esta HOJA DE '
            . 'ENCARGO PROFESIONAL y acuerdan suscribir el presente contrato, que se regirá por las siguientes:';
    }

    public static function estipulaciones(): string
    {
        return <<<'TEXT'
PRIMERA.- RÉGIMEN JURÍDICO
El presente contrato se regirá por la voluntad de las partes, manifestada en el mismo, y se otorgará, en su defecto, por lo dispuesto en el Código deontológico de la Abogacía Española y en el Código Civil.

SEGUNDA.- OBJETO
Con la firma de este documento el CLIENTE contrata los servicios de la ABOGADA, mediante el arrendamiento de sus servicios profesionales, regulado en el art. 1544 y siguientes del Código Civil, para el asunto que se indica seguidamente y sin que suponga este encargo profesional una relación laboral entre el cliente y la abogada.
Por su parte la ABOGADA acepta realizar la actuación profesional encomendada en el punto siguiente, con la máxima diligencia, en defensa de los intereses encomendados por su clienta, conforme a las exigencias técnicas, deontológicas y éticas adecuadas a la tutela jurídica del asunto; pudiendo auxiliarse de sus colaboradores y otros compañeros, quienes actuarán bajo su responsabilidad. Comprometiéndose a mantener informado al cliente de la marcha del asunto.
El encargo encomendado a la ABOGADA no se extenderá a otras tareas o actuaciones que no estén contempladas en el presupuesto.

TERCERA.- IDENTIFICACIÓN DE ASUNTO ENCOMENDADO
El CLIENTE, previa información de la ABOGADA, encarga a la misma, que acepta, la prestación de los siguientes servicios jurídicos y la realización de las siguientes actuaciones:
i) Actuación extrajudicial: estudio y asesoramiento para solicitar [[NOMBRE_TRAMITE]]
ii) Revisión o recopilación de documentos, según corresponda. (Será responsabilidad del CLIENTE la entrega oportuna de los documentos personales y necesarios que solicite la ABOGADA)
iii) Presentación de expediente
iv) Seguimiento de expediente

CUARTA.- HONORARIOS
Los honorarios que ambas partes pactan y que seguidamente se detallan, se corresponden a los honorarios de la ABOGADA por las actuaciones que la misma realice en ejecución del encargo antes descrito.
Los honorarios se presupuestan en [[HONORARIOS_LETRA]] ([[HONORARIOS_NUMERO]]).
Los honorarios fijados excluyen, correspondiendo al CLIENTE la contratación y el pago de los mismos:
i) Los honorarios de otros profesionales que deban intervenir (como, por ejemplo, notarios, procuradores y peritos).
ii) Los gastos en que se puede incurrir (desplazamientos, suplidos, comunicaciones, mensajeros, etc.)
iii) Gastos y costas del proceso.
iv) En cuanto a la tramitación judicial completa de los servicios encargados, se excluyen los recursos y los incidentes que no se hayan mencionado expresamente, así como las actuaciones profesionales derivadas de la ejecución. En este sentido la ABOGADA deberá informar previamente al cliente de los recursos e incidentes que se tengan que tramitar, el precio de los cuales se presupuestarán en función de su trascendencia y complejidad.

QUINTA.- MODO DE PAGO
Los honorarios profesionales se abonarán del siguiente modo:
[[PAGOS_PROGRAMADOS]]

El pago dentro del plazo arriba descrito se hará efectivo por el CLIENTE mediante transferencia bancaria, a la cuenta de la ABOGADA cuyos datos son los siguientes:
Titular: [[TITULAR_CUENTA]]
Entidad: [[ENTIDAD_BANCARIA]]
IBAN: [[IBAN]]

El resguardo de ingreso emitido por la entidad bancaria acreditará el pago de dicha cantidad salvo prueba en contrario.
La ABOGADA expedirá la correspondiente factura, que remitirá a la dirección de correo electrónico del CLIENTE.

SEXTA.- INFORMACIÓN AL CLIENTE Y AUTORIZACIÓN
La ABOGADA informa expresamente al CLIENTE de los siguientes puntos de interés:
i) Del posible resultado adverso del pleito.
ii) También se le informa de la posibilidad de recurrir a soluciones alternativas del conflicto, como la mediación, negociación, etc.
iii) Se le hace saber al cliente que la letrada, como profesional, está sujeto a las normas sobre prevención de blanqueo de capitales y financiación del terrorismo establecidas en la Ley 10/2010 y que el encargo encomendado podría estar fuera del ámbito de secreto profesional, por lo que, en caso de que las autoridades financieras requieran información sobre los datos obtenidos del cliente o el encargo efectuado, el letrado estaría obligado a facilitarlo.
iv) Por último, se le comunica que en caso de encontrarse en las circunstancias económicas exigidas por la Ley 1/1996, de 10 de enero, de Asistencia Jurídica Gratuita, podrá solicitar la misma.
El cliente autoriza al letrado a entregar copia de la documentación facilitada para cumplimentar el expediente (identificación, domicilio, actividad profesional) a otros terceros intervinientes o necesarios, para la realización del encargo, asesores fiscales, agentes inmobiliarios o entidades bancarias, caso de que dicha información sea requerida.

SÉPTIMA.- DERECHO DE DESISTIMIENTO Y FINALIZACIÓN ANTICIPADA DEL ENCARGO PROFESIONAL
EL CLIENTE puede ejercer, en cualquier momento y por escrito, su derecho a desistir de este contrato de manera libre y prescindir de los servicios de la ABOGADA, si bien tendrá la obligación de abonar la contraprestación económica acordada por la fase de cobro de honorarios que se encuentre en curso de preparación.
En caso de que la relación contractual entre la ABOGADA y el CLIENTE se extinga por la renuncia de la ABOGADA a la dirección del asunto antes de que recaiga una resolución judicial que ponga fin al procedimiento, los honorarios de la ABOGADA se limitarán a lo que se haya estipulado para las fases llevadas a cabo; debiendo éste realizar los actos necesarios para evitar la indefensión del CLIENTE.

OCTAVA.- PÓLIZA DE SEGURO PROFESIONAL
La ABOGADA tiene suscrita una póliza de seguro de responsabilidad civil profesional con la compañía Allianz, Compañía de Seguros y Reaseguros, S.A.

NOVENA.- RESOLUCIÓN DE CONFLICTOS ENTRE LAS PARTES
Se informa al CLIENTE que el [[COLEGIO_ABOGADOS]] cuenta con un servicio de quejas y reclamaciones, al que se podrá dirigir en cualquier momento para ser informado sobre sus derechos como consumidor de los servicios jurídicos contratados.
Para todas las cuestiones que, en su caso, pudieran surgir con motivo de la interpretación o cumplimiento del presente contrato, ambas partes con renuncia expresa a cualquier otro fuero que pudiera corresponder, se someten a la Jurisdicción y Competencia de los Juzgados y Tribunales de la ciudad de [[CIUDAD_DESPACHO]]; quedando facultada la ABOGADA para iniciar la oportuna reclamación judicial a través del procedimiento de jura de cuentas del letrado, previsto en el art. 35 y relacionados con este artículo de la LEC, o mediante el procedimiento judicial oportuno.
TEXT;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public static function proteccionDatosFilas(): array
    {
        return [
            ['label' => 'Responsable Identidad', 'value' => '[[NOMBRE_FIRMA]]'],
            ['label' => 'NIF', 'value' => '[[NIF_LETRADA]]'],
            ['label' => 'Contacto DPD', 'value' => '[[EMAIL_DESPACHO]]'],
            ['label' => 'Finalidad', 'value' => 'Realización de los encargos profesionales encomendados a la ABOGADA.'],
            ['label' => 'Legitimación', 'value' => 'Consentimiento expreso del interesado, la CLIENTE.'],
            [
                'label' => 'Destinatarios',
                'value' => 'Los posibles destinatarios serán las profesionales intervinientes en el proceso legal en cumplimiento del contrato de servicios u hoja de encargo profesional suscrita entre la ABOGADA y la CLIENTE.',
            ],
            [
                'label' => 'Derechos',
                'value' => 'En aras del cumplimiento del Reglamento (UE) 2016/679 del Parlamento Europeo y del Consejo, de 27 de abril de 2016, puede ejercer los derechos de acceso, rectificación, cancelación, limitación, oposición y portabilidad de manera gratuita mediante correo electrónico a [[EMAIL_DESPACHO]] o bien en la dirección [[DOMICILIO_DESPACHO]].',
            ],
            [
                'label' => 'Información adicional',
                'value' => 'Para obtener más información acerca de la política de protección de datos, puede ser requerida la misma al responsable del tratamiento de los datos, a través del email facilitado ([[EMAIL_DESPACHO]]).',
            ],
        ];
    }

    public static function cierre(): string
    {
        return 'Este documento ha sido entregado previamente al CLIENTE a fin de que procediera a su lectura '
            . 'sosegada y consultase y aclarase las dudas que el mismo eventualmente le pudiera suscitar con la '
            . 'ABOGADA. Luego, una vez leído y comprendido se aceptan plenamente ambas partes las estipulaciones de '
            . 'este acuerdo contractual, firmándolo conjuntamente en tantas copias como partes hay, y a un solo efecto, '
            . 'en el lugar y fecha expuestos en la cabecera del presente escrito.';
    }
}
