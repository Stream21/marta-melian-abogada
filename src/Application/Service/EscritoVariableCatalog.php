<?php

declare(strict_types=1);

namespace App\Application\Service;

final class EscritoVariableCatalog
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
                    ['key' => 'NIF_LETRADA', 'label' => 'NIF de la letrada'],
                    ['key' => 'COLEGIO_ABOGADOS', 'label' => 'Colegio de abogados'],
                    ['key' => 'DOMICILIO_DESPACHO', 'label' => 'Domicilio del despacho'],
                    ['key' => 'CIUDAD_DESPACHO', 'label' => 'Ciudad del despacho'],
                    ['key' => 'TELEFONO_DESPACHO', 'label' => 'Teléfono del despacho'],
                    ['key' => 'EMAIL_DESPACHO', 'label' => 'Email del despacho'],
                ],
            ],
            [
                'categoria' => 'Cliente',
                'variables' => [
                    ['key' => 'NOMBRE_CLIENTE', 'label' => 'Nombre del cliente'],
                    ['key' => 'NACIONALIDAD_CLIENTE', 'label' => 'Nacionalidad'],
                    ['key' => 'TIPO_DOCUMENTO_CLIENTE', 'label' => 'Tipo de documento'],
                    ['key' => 'NUM_DOCUMENTO_CLIENTE', 'label' => 'Número de documento'],
                    ['key' => 'DNI_CLIENTE', 'label' => 'DNI del cliente'],
                    ['key' => 'FECHA_NACIMIENTO_CLIENTE', 'label' => 'Fecha de nacimiento'],
                    ['key' => 'LUGAR_NACIMIENTO_CLIENTE', 'label' => 'Lugar de nacimiento'],
                    ['key' => 'DOMICILIO_CLIENTE', 'label' => 'Domicilio del cliente'],
                    ['key' => 'CP_CLIENTE', 'label' => 'Código postal'],
                    ['key' => 'TELEFONO_CLIENTE', 'label' => 'Teléfono del cliente'],
                    ['key' => 'EMAIL_CLIENTE', 'label' => 'Email del cliente'],
                ],
            ],
            [
                'categoria' => 'Trámite',
                'variables' => [
                    ['key' => 'NOMBRE_TRAMITE', 'label' => 'Nombre del trámite'],
                    ['key' => 'DESCRIPCION_ASUNTO', 'label' => 'Descripción del asunto'],
                ],
            ],
            [
                'categoria' => 'Económicas',
                'variables' => [
                    ['key' => 'CUANTIA_TOTAL', 'label' => 'Cuantía total'],
                    ['key' => 'HONORARIOS_TRAMITE', 'label' => 'Honorarios del trámite'],
                    ['key' => 'HONORARIOS_NUMERO', 'label' => 'Honorarios (número)'],
                    ['key' => 'HONORARIOS_LETRA', 'label' => 'Honorarios (letra)'],
                    ['key' => 'FORMA_PAGO', 'label' => 'Forma de pago'],
                ],
            ],
        ];
    }
}
