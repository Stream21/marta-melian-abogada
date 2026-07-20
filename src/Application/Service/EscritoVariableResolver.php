<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\Cliente;
use App\Domain\Entity\DespachoConfig;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\Tramite;

final class EscritoVariableResolver
{
    public function __construct(
        private CalendarioPagoService $calendarioPagoService,
        private EurosEnLetrasConverter $eurosEnLetras,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function previewValues(?DespachoConfig $despacho, ?Tramite $tramite): array
    {
        return $this->buildValues($despacho, $tramite, null, null);
    }

    /**
     * @return array<string, string>
     */
    public function resolve(
        ?DespachoConfig $despacho,
        ?Cliente $cliente,
        ?Tramite $tramite,
        ?Expediente $expediente,
    ): array {
        return $this->buildValues($despacho, $tramite, $cliente, $expediente);
    }

    /**
     * @param array<string, string> $values
     */
    public function substitute(string $text, array $values): string
    {
        return (string) preg_replace_callback(
            '/\[\[([A-Z_]+)\]\]/',
            static function (array $matches) use ($values): string {
                $key = $matches[1];

                return $values[$key] ?? $matches[0];
            },
            $text,
        );
    }

    /**
     * @return array<string, string>
     */
    private function buildValues(
        ?DespachoConfig $despacho,
        ?Tramite $tramite,
        ?Cliente $cliente,
        ?Expediente $expediente,
    ): array {
        $honorarios = null !== $expediente && $expediente->honorariosAcordados() > 0
            ? $expediente->honorariosAcordados()
            : ($tramite?->honorarios() ?? 450.0);
        $honorariosFormatted = number_format($honorarios, 2, ',', '.') . ' €';

        $formatter = new \IntlDateFormatter('es_ES', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE);

        $metodo = $expediente?->metodoPago();
        $plan = $expediente?->planPago();
        $numCuotas = $expediente?->numCuotas() ?? 1;

        $calendario = $expediente?->calendarioPagos();
        $provisional = null === $calendario;
        if ($provisional && null !== $expediente && null !== $plan) {
            $calendario = $this->calendarioPagoService->calcular(
                $honorarios,
                $plan,
                $numCuotas,
                new \DateTimeImmutable('today'),
            );
        }

        $formaPago = null !== $metodo && null !== $plan
            ? $this->calendarioPagoService->formaPagoLabel($metodo, $plan, $numCuotas)
            : 'Según calendario de pagos acordado';

        $formaPagoDetalle = null !== $metodo && null !== $plan
            ? $this->calendarioPagoService->formaPagoDetalle($metodo, $plan, $numCuotas, $honorarios)
            : 'Según calendario de pagos acordado';

        $values = [
            'FECHA_ACTUAL' => $formatter->format(new \DateTimeImmutable()) ?: (new \DateTimeImmutable())->format('d/m/Y'),
            'REFERENCIA_EXPEDIENTE' => $expediente?->numero() ?: 'HE-2024-0892-MMG',
            'NOMBRE_FIRMA' => $despacho?->nombreFirma() ?? 'Marta Melián Guerra',
            'NOMBRE_LETRADA' => $despacho?->nombreLetrada() ?? 'D.ª MARTA MELIAN GUERRA',
            'NUM_COLEGIADO' => $despacho?->numColegiado() ?? '7.111',
            'NIF_LETRADA' => $despacho?->nif() ?? '44737558-M',
            'COLEGIO_ABOGADOS' => $despacho?->colegioAbogados() ?? 'Ilustre Colegio de Abogados de Las Palmas',
            'SUBTITULO_PROFESIONAL' => $despacho?->subtituloProfesional() ?? 'Abogada y Mediadora',
            'DOMICILIO_DESPACHO' => $despacho?->direccion() ?? 'C. Picachos, 43, local 2, 35200 Telde',
            'CIUDAD_DESPACHO' => $despacho?->ciudad() ?? 'Las Palmas de Gran Canaria',
            'TELEFONO_DESPACHO' => $despacho?->telefono() ?? '+34 652 292 450',
            'EMAIL_DESPACHO' => $despacho?->email() ?? 'mmguerra.abogada@gmail.com',
            'WEB_DESPACHO' => $despacho?->web() ?? 'https://martamelianguerraabogados.com/',
            'IBAN' => $despacho?->iban() ?? 'ES46 3076 0770 1329 5326 4823',
            'ENTIDAD_BANCARIA' => $despacho?->entidadBancaria() ?? 'CAJASIETE CAJA RURAL',
            'TITULAR_CUENTA' => $despacho?->titularCuenta() ?? 'MARTA MELIAN GUERRA',
            'NOMBRE_CLIENTE' => 'Andreia Aelenei',
            'DNI_CLIENTE' => '063298218',
            'NACIONALIDAD_CLIENTE' => 'RUMANA',
            'TIPO_DOCUMENTO_CLIENTE' => 'PASAPORTE',
            'NUM_DOCUMENTO_CLIENTE' => '063298218',
            'FECHA_NACIMIENTO_CLIENTE' => '29.08.1987',
            'LUGAR_NACIMIENTO_CLIENTE' => 'Baia Sprie, Rumanía',
            'DOMICILIO_CLIENTE' => 'Calle Doctor Agustín Millares Carlo 5, 51, 35100',
            'CP_CLIENTE' => '35100',
            'TELEFONO_CLIENTE' => '+34 658 59 73 32',
            'EMAIL_CLIENTE' => '',
            'CUANTIA_TOTAL' => $honorariosFormatted,
            'HONORARIOS_TRAMITE' => $honorariosFormatted,
            'HONORARIOS_NUMERO' => $honorariosFormatted,
            'HONORARIOS_LETRA' => $this->eurosEnLetras->convertir($honorarios),
            'FORMA_PAGO' => $formaPago,
            'FORMA_PAGO_DETALLE' => $formaPagoDetalle,
            'PAGOS_PROGRAMADOS' => $this->calendarioPagoService->formatearPagosProgramados($calendario, $provisional),
            'DESCRIPCION_ASUNTO' => $tramite?->nombre() ?? 'Nacionalidad española por residencia',
            'NOMBRE_TRAMITE' => $tramite?->nombre() ?? 'Nacionalidad española por residencia',
        ];

        if (null !== $cliente) {
            $values['NOMBRE_CLIENTE'] = $cliente->nombre() ?: $values['NOMBRE_CLIENTE'];
            $values['DNI_CLIENTE'] = $cliente->numDocumento() ?: $values['DNI_CLIENTE'];
            $values['NACIONALIDAD_CLIENTE'] = $cliente->nacionalidad() ?: $values['NACIONALIDAD_CLIENTE'];
            $values['TIPO_DOCUMENTO_CLIENTE'] = $cliente->tipoDocumento() ?: $values['TIPO_DOCUMENTO_CLIENTE'];
            $values['NUM_DOCUMENTO_CLIENTE'] = $cliente->numDocumento() ?: $values['NUM_DOCUMENTO_CLIENTE'];
            $values['FECHA_NACIMIENTO_CLIENTE'] = null !== $cliente->fechaNacimiento()
                ? $cliente->fechaNacimiento()->format('d.m.Y')
                : $values['FECHA_NACIMIENTO_CLIENTE'];
            $values['LUGAR_NACIMIENTO_CLIENTE'] = $cliente->lugarNacimiento() ?: $values['LUGAR_NACIMIENTO_CLIENTE'];
            $values['DOMICILIO_CLIENTE'] = $cliente->domicilioCompleto() ?: $values['DOMICILIO_CLIENTE'];
            $values['CP_CLIENTE'] = $cliente->codigoPostal() ?: $values['CP_CLIENTE'];
            $values['TELEFONO_CLIENTE'] = $cliente->telefono() ?: $values['TELEFONO_CLIENTE'];
            $values['EMAIL_CLIENTE'] = $cliente->email() ?: $values['EMAIL_CLIENTE'];
        } elseif (null !== $expediente && '' !== trim($expediente->clientName())) {
            $values['NOMBRE_CLIENTE'] = $expediente->clientName();
        }

        return $values;
    }
}

