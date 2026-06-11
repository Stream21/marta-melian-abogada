<?php

declare(strict_types=1);

namespace App\Application\Service;

use Ramsey\Uuid\Uuid;

final class HojaEncargoDefaultPlantillaFactory
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function createDefault(): array
    {
        return [
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'header',
                'title' => 'HOJA DE ENCARGO',
                'showLogo' => true,
                'showReferencia' => true,
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'text',
                'content' => 'En la ciudad de [[CIUDAD_DESPACHO]], a [[FECHA_ACTUAL]],',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'section',
                'title' => 'REUNIDOS',
                'content' => 'De una parte, D./Dña. [[NOMBRE_CLIENTE]], con DNI [[DNI_CLIENTE]] y domicilio en [[DOMICILIO_CLIENTE]], en adelante «el Cliente».' . "\n\n"
                    . 'De otra parte, [[NOMBRE_LETRADA]], Letrada del Ilustre Colegio de Abogados de Madrid, con número de colegiado [[NUM_COLEGIADO]], actuando en nombre y representación de [[NOMBRE_FIRMA]], con domicilio profesional en [[DOMICILIO_DESPACHO]], en adelante «la Letrada».',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'section',
                'title' => 'OBJETO DEL ENCARGO',
                'content' => 'Inserte aquí la descripción detallada del procedimiento legal...',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'section',
                'title' => 'HONORARIOS',
                'content' => 'Los honorarios profesionales por la prestación de servicios jurídicos descritos ascienden a [[CUANTIA_TOTAL]] euros ([[HONORARIOS_TRAMITE]] € en concepto de honorarios del trámite).' . "\n\n"
                    . 'Forma de pago: [[FORMA_PAGO]].',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'signature_client',
                'label' => 'POR EL CLIENTE',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'signature_lawyer',
                'label' => 'POR LA LETRADA',
            ],
            [
                'id' => Uuid::uuid4()->toString(),
                'type' => 'footer',
                'showPagination' => true,
            ],
        ];
    }
}
