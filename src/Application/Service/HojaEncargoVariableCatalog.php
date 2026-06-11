<?php

declare(strict_types=1);

namespace App\Application\Service;

final class HojaEncargoVariableCatalog
{
    /**
     * @return list<array{categoria: string, variables: list<array{key: string, label: string}>}>
     */
    public function all(): array
    {
        return [
            [
                'categoria' => 'Sistema',
                'variables' => [
                    ['key' => 'FECHA_ACTUAL', 'label' => 'Fecha actual'],
                    ['key' => 'REFERENCIA_EXPEDIENTE', 'label' => 'Referencia del expediente'],
                ],
            ],
            [
                'categoria' => 'Despacho',
                'variables' => [
                    ['key' => 'NOMBRE_FIRMA', 'label' => 'Nombre de la firma'],
                    ['key' => 'NOMBRE_LETRADA', 'label' => 'Nombre de la letrada'],
                    ['key' => 'NUM_COLEGIADO', 'label' => 'Número de colegiado'],
                    ['key' => 'DOMICILIO_DESPACHO', 'label' => 'Domicilio del despacho'],
                    ['key' => 'CIUDAD_DESPACHO', 'label' => 'Ciudad del despacho'],
                ],
            ],
            [
                'categoria' => 'Cliente',
                'variables' => [
                    ['key' => 'NOMBRE_CLIENTE', 'label' => 'Nombre del cliente'],
                    ['key' => 'DNI_CLIENTE', 'label' => 'DNI del cliente'],
                    ['key' => 'DOMICILIO_CLIENTE', 'label' => 'Domicilio del cliente'],
                ],
            ],
            [
                'categoria' => 'Económicas',
                'variables' => [
                    ['key' => 'CUANTIA_TOTAL', 'label' => 'Cuantía total'],
                    ['key' => 'HONORARIOS_TRAMITE', 'label' => 'Honorarios del trámite'],
                    ['key' => 'FORMA_PAGO', 'label' => 'Forma de pago'],
                ],
            ],
        ];
    }
}
