<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Application\DTO\ClienteInputMapper;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\DespachoConfigRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\TramiteId;

final class ContratacionAccesoPresenter
{
    public function __construct(
        private ContratacionRepositoryInterface $contratacionRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private TramiteRepositoryInterface $tramiteRepository,
        private TramiteDocumentoRequeridoRepositoryInterface $documentoRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteFirmaRepositoryInterface $firmaRepository,
        private DespachoConfigRepositoryInterface $despachoRepository,
        private FirmaOtpService $firmaOtpService,
        private CalendarioPagoService $calendarioPagoService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Expediente $expediente, string $accessToken): array
    {
        $pasoActivo = null;
        $pasos = [];

        if (
            FaseNegocioExpediente::Contratacion === $expediente->faseNegocio()
            && null !== $expediente->tramiteId()
        ) {
            $pasosDb = $this->contratacionRepository->findPasosByExpediente($expediente->id());
            usort($pasosDb, fn ($a, $b) => $a->paso()->orden() <=> $b->paso()->orden());
            $pasoActivo = $this->resolverPasoActivoCliente($pasosDb);
            $pasos = array_map(fn ($p) => [
                'paso' => $p->paso()->value,
                'label' => $p->paso()->label(),
                'estado' => $p->estado()->value,
                'estadoLabel' => $p->estado()->label(),
                'esActivo' => $pasoActivo === $p->paso(),
                'notaDevolucion' => $p->notaDevolucion(),
                'motivosDevolucion' => $p->motivosDevolucion(),
            ], $pasosDb);
        }

        $entregados = [];
        foreach ($this->documentoEntregadoRepository->findByExpediente($expediente->id()) as $doc) {
            $tramiteDocId = $doc->documentoRequeridoId();
            if (null === $tramiteDocId) {
                continue;
            }
            $entregados[$tramiteDocId->value()] = $doc;
        }

        $documentosRequeridos = [];
        if (null !== $expediente->tramiteId()) {
            $docs = $this->documentoRepository->findByTramiteId(new TramiteId($expediente->tramiteId()));
            foreach ($docs as $doc) {
                if ($doc->fase() !== FaseDocumentoTramite::DocumentacionBasica) {
                    continue;
                }
                $entregado = $entregados[$doc->id()->value()] ?? null;
                $documentosRequeridos[] = [
                    'id' => $doc->id()->value(),
                    'nombre' => $doc->nombre(),
                    'descripcion' => $doc->descripcion(),
                    'obligatorio' => $doc->obligatorio(),
                    'tipo' => $doc->tipo()->value,
                    'maxImagenes' => $doc->maxImagenes(),
                    'estado' => $entregado?->estado()->value ?? EstadoDocumentoEntregado::Pendiente->value,
                    'entregadoAt' => $entregado?->entregadoAt()->format(\DateTimeInterface::ATOM),
                ];
            }
        }

        $firmasRegistradas = [];
        foreach ($this->firmaRepository->findByExpediente($expediente->id()) as $firma) {
            $firmasRegistradas[$firma->tipoEscrito()->value] = true;
        }

        $documentosFirma = array_map(
            fn (TipoEscrito $tipo) => [
                'tipo' => $tipo->value,
                'label' => $tipo->label(),
                'previewUrl' => sprintf('/api/acceso/%s/firma/%s/pdf', $accessToken, $tipo->value),
                'firmado' => isset($firmasRegistradas[$tipo->value]),
                'firmadoPdfUrl' => isset($firmasRegistradas[$tipo->value])
                    ? sprintf('/api/acceso/%s/firma/%s/firmado.pdf', $accessToken, $tipo->value)
                    : null,
            ],
            [TipoEscrito::HojaEncargo, TipoEscrito::Designacion, TipoEscrito::Rgpd],
        );

        $despacho = $this->despachoRepository->find();
        $calendario = $expediente->calendarioPagos();
        $importePagoInicial = $this->calendarioPagoService->importePagoInicial(
            $expediente->honorariosAcordados(),
            $expediente->planPago(),
            $expediente->numCuotas(),
            $calendario,
        );

        $resumenPago = [
            'honorariosAcordados' => $expediente->honorariosAcordados(),
            'metodoPago' => $expediente->metodoPago()->value,
            'metodoPagoLabel' => $expediente->metodoPago()->label(),
            'planPago' => $expediente->planPago()->value,
            'planPagoLabel' => $expediente->planPago()->label(),
            'numCuotas' => $expediente->numCuotas(),
            'importeCuota' => $importePagoInicial,
            'importePagoInicial' => $importePagoInicial,
            'iban' => $despacho?->iban() ?? '',
            'titularCuenta' => $despacho?->titularCuenta() ?? '',
            'entidadBancaria' => $despacho?->entidadBancaria() ?? '',
            'calendarioPago' => $calendario,
            'calendarioProyectado' => null === $calendario
                ? $this->calendarioPagoService->calcular(
                    $expediente->honorariosAcordados(),
                    $expediente->planPago(),
                    $expediente->numCuotas(),
                    new \DateTimeImmutable('today'),
                )
                : null,
            'fechaFirmaContrato' => $expediente->fechaFirmaContrato()?->format(\DateTimeInterface::ATOM),
        ];

        $clienteDatos = null;
        $datosClienteEditables = null;
        $identidadEdicion = null;
        $nombreCliente = '' !== trim($expediente->clientName()) ? trim($expediente->clientName()) : null;
        if (null !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $nombreCliente = $cliente->nombre();
                $clienteDatos = [
                    'nombre' => $cliente->nombre(),
                    'tipoDocumento' => $cliente->tipoDocumento(),
                    'numDocumento' => $this->maskDocumento($cliente->numDocumento()),
                    'telefono' => $this->maskTelefono($cliente->telefono()),
                    'email' => $this->maskEmail($cliente->email()),
                    'domicilio' => $cliente->domicilio(),
                    'ciudad' => $cliente->ciudad(),
                ];

                $pasoDatos = $this->contratacionRepository->findPaso($expediente->id(), PasoContratacionCliente::DatosCliente);
                $pasoDatosPendiente = null !== $pasoDatos
                    && EstadoPasoContratacion::Pendiente === $pasoDatos->estado();
                $tieneDocumento = $cliente->tieneDocumentoIdentidad();
                $esCorreccion = $pasoDatosPendiente
                    && null !== $pasoDatos->notaDevolucion()
                    && $tieneDocumento;

                if ($pasoDatosPendiente && $tieneDocumento) {
                    $datosClienteEditables = ClienteInputMapper::toArray(ClienteInputMapper::fromCliente($cliente));
                    $tipoDoc = $cliente->documentoIdentidadTipo();
                    $identidadEdicion = [
                        'modoCorreccion' => $esCorreccion,
                        'tieneDocumentoPrevio' => true,
                        'tipoEscaneo' => $tipoDoc?->value,
                        'anversoUrl' => sprintf('/api/acceso/%s/identidad/anverso', rawurlencode($accessToken)),
                        'reversoUrl' => null !== $cliente->documentoIdentidadReversoPath()
                            ? sprintf('/api/acceso/%s/identidad/reverso', rawurlencode($accessToken))
                            : null,
                        'motivosDevolucion' => $esCorreccion ? $pasoDatos->motivosDevolucion() : [],
                    ];
                }
            }
        }

        return [
            'pasoActivo' => $pasoActivo?->value,
            'pasos' => $pasos,
            'documentosRequeridos' => $documentosRequeridos,
            'documentosFirma' => $documentosFirma,
            'resumenPago' => $resumenPago,
            'clienteDatos' => $clienteDatos,
            'datosClienteEditables' => $datosClienteEditables,
            'identidadEdicion' => $identidadEdicion,
            'clienteNombre' => $nombreCliente,
            'despachoLogoUrl' => null !== $despacho?->logoPath()
                ? sprintf('/api/acceso/%s/despacho/logo', rawurlencode($accessToken))
                : null,
            'despachoNombreFirma' => null !== $despacho && '' !== trim($despacho->nombreFirma())
                ? trim($despacho->nombreFirma())
                : 'Bufete Melián',
            'despachoSubtitulo' => null !== $despacho && '' !== trim($despacho->subtituloProfesional())
                ? trim($despacho->subtituloProfesional())
                : 'Servicios Jurídicos Profesionales',
            'firmas' => $this->serializarConfigFirmas($expediente),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializarConfigFirmas(Expediente $expediente): array
    {
        $requiereOtp = true;
        if (null !== $expediente->tramiteId()) {
            $tramite = $this->tramiteRepository->findById(new TramiteId($expediente->tramiteId()));
            $requiereOtp = null === $tramite || $tramite->requiereOtpFirma();
        }

        $telefonoMascara = null;
        if (null !== $expediente->clienteId()) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente && '' !== trim($cliente->telefono())) {
                $telefonoMascara = $this->maskTelefono($cliente->telefono());
            }
        }

        return [
            'requiereOtp' => $requiereOtp,
            'otpVerificado' => $requiereOtp && $this->firmaOtpService->sesionVerificada($expediente->id()),
            'telefonoMascara' => $telefonoMascara,
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
