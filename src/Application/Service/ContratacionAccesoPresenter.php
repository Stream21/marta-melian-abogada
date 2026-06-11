<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\DespachoConfigRepositoryInterface;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\TramiteId;

final class ContratacionAccesoPresenter
{
    public function __construct(
        private ContratacionRepositoryInterface $contratacionRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private TramiteDocumentoRequeridoRepositoryInterface $documentoRepository,
        private DespachoConfigRepositoryInterface $despachoRepository,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Expediente $expediente, string $accessToken): array
    {
        $pasoActivo = null;
        $pasos = [];

        if (null !== $expediente->tramiteId()) {
            $pasosDb = $this->contratacionRepository->findPasosByExpediente($expediente->id());
            usort($pasosDb, fn ($a, $b) => $a->paso()->orden() <=> $b->paso()->orden());
            $pasoActivo = $this->resolverPasoActivoCliente($pasosDb);
            $pasos = array_map(fn ($p) => [
                'paso' => $p->paso()->value,
                'label' => $p->paso()->label(),
                'estado' => $p->estado()->value,
                'estadoLabel' => $p->estado()->label(),
                'esActivo' => $pasoActivo === $p->paso(),
            ], $pasosDb);
        }

        $documentosRequeridos = [];
        if (null !== $expediente->tramiteId()) {
            $docs = $this->documentoRepository->findByTramiteId(new TramiteId($expediente->tramiteId()));
            foreach ($docs as $doc) {
                if ($doc->fase() !== FaseDocumentoTramite::DocumentosCliente) {
                    continue;
                }
                $documentosRequeridos[] = [
                    'id' => $doc->id()->value(),
                    'nombre' => $doc->nombre(),
                    'descripcion' => $doc->descripcion(),
                    'obligatorio' => $doc->obligatorio(),
                    'tipo' => $doc->tipo()->value,
                    'maxImagenes' => $doc->maxImagenes(),
                ];
            }
        }

        $documentosFirma = array_map(
            fn (TipoEscrito $tipo) => [
                'tipo' => $tipo->value,
                'label' => $tipo->label(),
                'previewUrl' => sprintf('/api/acceso/%s/firma/%s/pdf', $accessToken, $tipo->value),
            ],
            [TipoEscrito::HojaEncargo, TipoEscrito::Designacion, TipoEscrito::Rgpd],
        );

        $despacho = $this->despachoRepository->find();
        $importeCuota = $expediente->numCuotas() > 0
            ? round($expediente->honorariosAcordados() / $expediente->numCuotas(), 2)
            : $expediente->honorariosAcordados();

        $resumenPago = [
            'honorariosAcordados' => $expediente->honorariosAcordados(),
            'metodoPago' => $expediente->metodoPago()->value,
            'metodoPagoLabel' => $expediente->metodoPago()->label(),
            'planPago' => $expediente->planPago()->value,
            'numCuotas' => $expediente->numCuotas(),
            'importeCuota' => $importeCuota,
            'iban' => $despacho?->iban() ?? '',
            'titularCuenta' => $despacho?->titularCuenta() ?? '',
            'entidadBancaria' => $despacho?->entidadBancaria() ?? '',
        ];

        $clienteDatos = null;
        if (null !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $clienteDatos = [
                    'nombre' => $cliente->nombre(),
                    'tipoDocumento' => $cliente->tipoDocumento(),
                    'numDocumento' => $this->maskDocumento($cliente->numDocumento()),
                    'telefono' => $this->maskTelefono($cliente->telefono()),
                    'email' => $this->maskEmail($cliente->email()),
                    'domicilio' => $cliente->domicilio(),
                    'ciudad' => $cliente->ciudad(),
                ];
            }
        }

        return [
            'pasoActivo' => $pasoActivo?->value,
            'pasos' => $pasos,
            'documentosRequeridos' => $documentosRequeridos,
            'documentosFirma' => $documentosFirma,
            'resumenPago' => $resumenPago,
            'clienteDatos' => $clienteDatos,
        ];
    }

    /**
     * @param \App\Domain\Entity\ContratacionPaso[] $pasos
     */
    private function resolverPasoActivoCliente(array $pasos): ?PasoContratacionCliente
    {
        $porPaso = [];
        foreach ($pasos as $paso) {
            $porPaso[$paso->paso()->value] = $paso;
        }

        foreach (PasoContratacionCliente::ordenados() as $ordenPaso) {
            $paso = $porPaso[$ordenPaso->value] ?? null;
            if (null === $paso) {
                continue;
            }

            if ($paso->estado() === EstadoPasoContratacion::ValidadoAbogado) {
                continue;
            }

            if ($paso->estado() === EstadoPasoContratacion::RealizadoCliente) {
                return null;
            }

            return $ordenPaso;
        }

        return null;
    }

    private function maskTelefono(string $telefono): string
    {
        $len = strlen($telefono);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . substr($telefono, -4);
    }

    private function maskDocumento(string $documento): string
    {
        $len = strlen($documento);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return str_repeat('*', $len - 4) . substr($documento, -4);
    }

    private function maskEmail(string $email): string
    {
        if ('' === $email || !str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $maskedLocal = strlen($local) <= 1 ? '*' : $local[0] . str_repeat('*', max(1, strlen($local) - 1));

        return $maskedLocal . '@' . $domain;
    }
}
