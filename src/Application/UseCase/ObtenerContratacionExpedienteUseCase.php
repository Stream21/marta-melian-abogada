<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\CalendarioPagoService;
use App\Application\Service\DocumentoIntegridadService;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\MetodoPagoExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;

final class ObtenerContratacionExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ExpedienteFirmaRepositoryInterface $firmaRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private DocumentoIntegridadService $integridadService,
        private CalendarioPagoService $calendarioPagoService,
        private ActualizarCondicionesPagoContratacionUseCase $condicionesPagoEditable,
        private InicializarContratacionUseCase $inicializarContratacion,
        private string $frontendBaseUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $expedienteId): array
    {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if ($expediente->faseNegocio() === FaseNegocioExpediente::Contratacion) {
            ($this->inicializarContratacion)($id);
        }

        $pasos = $this->contratacionRepository->findPasosByExpediente($id);
        usort($pasos, fn ($a, $b) => $a->paso()->orden() <=> $b->paso()->orden());

        $hitosTotal = $this->contratacionRepository->countHitosByExpediente($id);
        $hitos = $this->contratacionRepository->findHitosByExpedientePaginated($id, 0, 15);

        $pasoActivo = $this->resolverPasoActivo($pasos);
        $todosValidados = $this->todosValidados($pasos);

        $accessUrl = null;
        if (null !== $expediente->accessToken()) {
            $accessUrl = rtrim($this->frontendBaseUrl, '/') . '/acceso/' . $expediente->accessToken();
        }

        $calendario = $expediente->calendarioPagos();
        $calendarioProyectado = null === $calendario
            ? $this->calendarioPagoService->calcular(
                $expediente->honorariosAcordados(),
                $expediente->planPago(),
                $expediente->numCuotas(),
                new \DateTimeImmutable('today'),
            )
            : null;

        $importePagoInicial = $this->calendarioPagoService->importePagoInicial(
            $expediente->honorariosAcordados(),
            $expediente->planPago(),
            $expediente->numCuotas(),
            $calendario,
        );

        return [
            'expedienteId' => $expediente->id()->value(),
            'numero' => $expediente->numero(),
            'clienteId' => $expediente->clienteId(),
            'faseNegocio' => $expediente->faseNegocio()->value,
            'faseNegocioLabel' => $expediente->faseNegocio()->label(),
            'estadoFase' => $expediente->estadoFase()->value,
            'estadoFaseLabel' => $expediente->estadoFase()->label(),
            'metodoPago' => $expediente->metodoPago()->value,
            'metodoPagoLabel' => $expediente->metodoPago()->label(),
            'planPago' => $expediente->planPago()->value,
            'honorariosAcordados' => $expediente->honorariosAcordados(),
            'importePagoInicial' => $importePagoInicial,
            'numCuotas' => $expediente->numCuotas(),
            'accessUrl' => $accessUrl,
            'fechaVencimientoFase' => $expediente->fechaVencimientoFase()?->format('Y-m-d'),
            'pasoActivo' => $pasoActivo?->value,
            'contratacionCompletada' => $todosValidados,
            'pasos' => array_map(
                fn ($p) => [
                    'paso' => $p->paso()->value,
                    'label' => $p->paso()->label(),
                    'descripcion' => $p->paso()->descripcion(),
                    'orden' => $p->paso()->orden(),
                    'estado' => $p->estado()->value,
                    'estadoLabel' => $p->estado()->label(),
                    'realizadoAt' => $p->realizadoAt()?->format(\DateTimeInterface::ATOM),
                    'validadoAt' => $p->validadoAt()?->format(\DateTimeInterface::ATOM),
                    'requiereValidacionAbogado' => $this->requiereValidacionAbogado($p, $expediente, $pasos),
                    'notaDevolucion' => $p->notaDevolucion(),
                ],
                $pasos,
            ),
            'hitos' => array_map(fn ($h) => [
                'id' => $h->id(),
                'paso' => $h->paso()?->value,
                'tipo' => $h->tipo(),
                'descripcion' => $h->descripcion(),
                'actor' => $h->actor()->value,
                'createdAt' => $h->createdAt()->format(\DateTimeInterface::ATOM),
            ], $hitos),
            'hitosTotal' => $hitosTotal,
            'firmasDocumento' => $this->serializarFirmas($id),
            'fechaFirmaContrato' => $expediente->fechaFirmaContrato()?->format(\DateTimeInterface::ATOM),
            'calendarioPago' => $calendario,
            'calendarioProyectado' => $calendarioProyectado,
            'condicionesPagoEditables' => $this->condicionesPagoEditable->puedeEditarCondicionesPago($id),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializarFirmas(ExpedienteId $expedienteId): array
    {
        $firmasPorTipo = [];
        foreach ($this->firmaRepository->findByExpediente($expedienteId) as $firma) {
            $firmasPorTipo[$firma->tipoEscrito()->value] = $firma;
        }

        $resultado = [];
        foreach ([TipoEscrito::HojaEncargo, TipoEscrito::Designacion, TipoEscrito::Rgpd] as $tipo) {
            $firma = $firmasPorTipo[$tipo->value] ?? null;
            $integridadOk = null;
            $hash = $firma?->pdfFirmadoSha256();

            if (null !== $firma && null !== $hash && null !== $firma->pdfFirmadoPath()) {
                try {
                    $content = $this->fileStorage->readRelativePath($firma->pdfFirmadoPath());
                    $integridadOk = $this->integridadService->verificar($content, $hash);
                } catch (\InvalidArgumentException) {
                    $integridadOk = false;
                }
            }

            $resultado[] = [
                'tipo' => $tipo->value,
                'label' => $tipo->label(),
                'firmado' => null !== $firma,
                'firmadoAt' => $firma?->firmadoAt()->format(\DateTimeInterface::ATOM),
                'pdfSha256' => $hash,
                'integridadOk' => $integridadOk,
            ];
        }

        return $resultado;
    }

    /**
     * @param \App\Domain\Entity\ContratacionPaso[] $pasos
     */
    private function requiereValidacionAbogado(
        \App\Domain\Entity\ContratacionPaso $paso,
        Expediente $expediente,
        array $pasos,
    ): bool {
        if (EstadoPasoContratacion::RealizadoCliente === $paso->estado()) {
            return true;
        }

        if (PasoContratacionCliente::Pago !== $paso->paso()
            || MetodoPagoExpediente::Manual !== $expediente->metodoPago()
            || EstadoPasoContratacion::Pendiente !== $paso->estado()) {
            return false;
        }

        foreach ($pasos as $otroPaso) {
            if (PasoContratacionCliente::Firmas === $otroPaso->paso()) {
                return EstadoPasoContratacion::ValidadoAbogado === $otroPaso->estado();
            }
        }

        return false;
    }

    /**
     * @param \App\Domain\Entity\ContratacionPaso[] $pasos
     */
    private function resolverPasoActivo(array $pasos): ?PasoContratacionCliente
    {
        foreach (PasoContratacionCliente::ordenados() as $ordenPaso) {
            foreach ($pasos as $paso) {
                if ($paso->paso() === $ordenPaso && $paso->estado()->value !== 'validado_abogado') {
                    return $ordenPaso;
                }
            }
        }

        return null;
    }

    /**
     * @param \App\Domain\Entity\ContratacionPaso[] $pasos
     */
    private function todosValidados(array $pasos): bool
    {
        if (count($pasos) < 3) {
            return false;
        }

        foreach (PasoContratacionCliente::ordenados() as $pasoEsperado) {
            $encontrado = false;
            foreach ($pasos as $paso) {
                if ($paso->paso() === $pasoEsperado) {
                    $encontrado = true;
                    if ($paso->estado()->value !== 'validado_abogado') {
                        return false;
                    }
                    break;
                }
            }
            if (!$encontrado) {
                return false;
            }
        }

        return true;
    }
}
