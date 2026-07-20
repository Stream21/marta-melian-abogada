<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\EstadoDocumentoEntregado;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\ExpedienteDocumentoRequerido;
use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\OrigenDocumentoRequeridoExpediente;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ServicioDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ServicioId;
use App\Domain\ValueObject\TramiteId;

/**
 * Mantiene los requisitos del expediente alineados con la plantilla vigente
 * (servicio + trámite) y elimina duplicados heredados de configuraciones antiguas.
 */
final class RequerimientosDuplicadosService
{
    /** @var array<string, string> */
    private const FRAGMENTOS_SEMANTICOS = [
        'antecedentes_penales' => 'antecedentes penales',
        'pasaporte' => 'pasaporte',
    ];

    public function __construct(
        private ExpedienteDocumentoRequeridoRepositoryInterface $requeridoRepository,
        private ExpedienteDocumentoRepositoryInterface $entregadoRepository,
        private ServicioDocumentoRequeridoRepositoryInterface $servicioDocumentoRepository,
        private TramiteDocumentoRequeridoRepositoryInterface $tramiteDocumentoRepository,
    ) {
    }

    public function reconciliar(ExpedienteId $expedienteId, Expediente $expediente): void
    {
        $entregas = $this->indexarEntregas($expedienteId);

        $this->eliminarHuerfanosDePlantilla($expedienteId, $expediente, $entregas);
        $this->eliminarDuplicadosSemánticos($expedienteId, $entregas);
        $this->eliminarDuplicadosPorNombre($expedienteId, $entregas);
    }

    public function normalizarNombre(string $nombre): string
    {
        return mb_strtolower(trim($nombre));
    }

    public function claveSemantica(string $nombre): ?string
    {
        $normalizado = $this->normalizarNombre($nombre);
        foreach (self::FRAGMENTOS_SEMANTICOS as $clave => $fragmento) {
            if (str_contains($normalizado, $fragmento)) {
                return $clave;
            }
        }

        return null;
    }

    /**
     * @param array<string, true> $nombresServicio
     * @param array<string, true> $clavesServicio
     */
    public function tramiteDuplicaServicio(
        string $nombreTramite,
        array $nombresServicio,
        array $clavesServicio,
    ): bool {
        $nombre = $this->normalizarNombre($nombreTramite);
        if (isset($nombresServicio[$nombre])) {
            return true;
        }

        $clave = $this->claveSemantica($nombreTramite);

        return null !== $clave && isset($clavesServicio[$clave]);
    }

    /**
     * @param array<string, ExpedienteDocumentoEntregado> $entregas
     */
    private function eliminarHuerfanosDePlantilla(
        ExpedienteId $expedienteId,
        Expediente $expediente,
        array $entregas,
    ): void {
        $idsServicioVigentes = $this->idsServicioVigentes($expediente);
        $idsTramiteVigentes = $this->idsTramiteVigentes($expediente);

        foreach ($this->requeridoRepository->findByExpediente($expedienteId) as $doc) {
            if (!$this->esHuerfanoDePlantilla($doc, $idsServicioVigentes, $idsTramiteVigentes)) {
                continue;
            }

            $this->eliminarSiEsSeguro($doc, $entregas);
        }
    }

    /**
     * @param array<string, true> $idsServicioVigentes
     * @param array<string, true> $idsTramiteVigentes
     */
    private function esHuerfanoDePlantilla(
        ExpedienteDocumentoRequerido $doc,
        array $idsServicioVigentes,
        array $idsTramiteVigentes,
    ): bool {
        if (OrigenDocumentoRequeridoExpediente::Manual === $doc->origen()) {
            return false;
        }

        if (OrigenDocumentoRequeridoExpediente::Servicio === $doc->origen()) {
            $plantillaId = $doc->servicioDocumentoRequeridoId();
            if (null === $plantillaId) {
                return false;
            }

            return !isset($idsServicioVigentes[$plantillaId->value()]);
        }

        $plantillaId = $doc->tramiteDocumentoRequeridoId();
        if (null === $plantillaId) {
            return false;
        }

        return !isset($idsTramiteVigentes[$plantillaId->value()]);
    }

    /**
     * @return array<string, true>
     */
    private function idsServicioVigentes(Expediente $expediente): array
    {
        $servicioId = $expediente->servicioId();
        if (null === $servicioId || '' === $servicioId) {
            return [];
        }

        $ids = [];
        foreach ($this->servicioDocumentoRepository->findByServicioId(new ServicioId($servicioId)) as $doc) {
            if (FaseDocumentoTramite::DocumentosCliente !== $doc->fase()) {
                continue;
            }
            $ids[$doc->id()->value()] = true;
        }

        return $ids;
    }

    /**
     * @return array<string, true>
     */
    private function idsTramiteVigentes(Expediente $expediente): array
    {
        $tramiteId = $expediente->tramiteId();
        if (null === $tramiteId || '' === $tramiteId) {
            return [];
        }

        $ids = [];
        foreach ($this->tramiteDocumentoRepository->findByTramiteId(new TramiteId($tramiteId)) as $doc) {
            if (FaseDocumentoTramite::DocumentosCliente !== $doc->fase()) {
                continue;
            }
            $ids[$doc->id()->value()] = true;
        }

        return $ids;
    }

    /**
     * @param array<string, ExpedienteDocumentoEntregado> $entregas
     */
    private function eliminarDuplicadosSemánticos(ExpedienteId $expedienteId, array $entregas): void
    {
        $porClave = [];
        foreach ($this->requeridoRepository->findByExpediente($expedienteId) as $doc) {
            $clave = $this->claveSemantica($doc->nombre());
            if (null === $clave) {
                continue;
            }
            $porClave[$clave][] = $doc;
        }

        foreach ($porClave as $grupo) {
            if (count($grupo) < 2) {
                continue;
            }

            $tieneServicio = false;
            foreach ($grupo as $doc) {
                if (OrigenDocumentoRequeridoExpediente::Servicio === $doc->origen()) {
                    $tieneServicio = true;
                    break;
                }
            }

            if (!$tieneServicio) {
                continue;
            }

            $conservar = $this->elegirDocumentoAConservar($grupo);
            foreach ($grupo as $doc) {
                if ($doc->id()->value() === $conservar->id()->value()) {
                    continue;
                }

                $this->eliminarSiEsSeguro($doc, $entregas);
            }
        }
    }

    /**
     * @param array<string, ExpedienteDocumentoEntregado> $entregas
     */
    private function eliminarDuplicadosPorNombre(ExpedienteId $expedienteId, array $entregas): void
    {
        $porNombre = [];
        foreach ($this->requeridoRepository->findByExpediente($expedienteId) as $doc) {
            $porNombre[$this->normalizarNombre($doc->nombre())][] = $doc;
        }

        foreach ($porNombre as $grupo) {
            if (count($grupo) < 2) {
                continue;
            }

            $conservar = $this->elegirDocumentoAConservar($grupo);
            foreach ($grupo as $doc) {
                if ($doc->id()->value() === $conservar->id()->value()) {
                    continue;
                }

                $this->eliminarSiEsSeguro($doc, $entregas);
            }
        }
    }

    /**
     * @return array<string, ExpedienteDocumentoEntregado>
     */
    private function indexarEntregas(ExpedienteId $expedienteId): array
    {
        $entregas = [];
        foreach ($this->entregadoRepository->findByExpediente($expedienteId) as $entrega) {
            $docId = $entrega->expedienteDocumentoRequeridoId();
            if (null !== $docId) {
                $entregas[$docId->value()] = $entrega;
            }
        }

        return $entregas;
    }

    /**
     * @param array<string, ExpedienteDocumentoEntregado> $entregas
     */
    private function eliminarSiEsSeguro(ExpedienteDocumentoRequerido $doc, array $entregas): void
    {
        $entrega = $entregas[$doc->id()->value()] ?? null;
        if (!$this->puedeEliminar($entrega)) {
            return;
        }

        if (null !== $entrega) {
            $this->entregadoRepository->deleteById($entrega->id());
            unset($entregas[$doc->id()->value()]);
        }

        $this->requeridoRepository->delete($doc->id());
    }

    /**
     * @param list<ExpedienteDocumentoRequerido> $grupo
     */
    private function elegirDocumentoAConservar(array $grupo): ExpedienteDocumentoRequerido
    {
        usort($grupo, function (ExpedienteDocumentoRequerido $a, ExpedienteDocumentoRequerido $b): int {
            $prioridad = static fn (ExpedienteDocumentoRequerido $doc): int => match ($doc->origen()) {
                OrigenDocumentoRequeridoExpediente::Servicio => 0,
                OrigenDocumentoRequeridoExpediente::Tramite => 1,
                OrigenDocumentoRequeridoExpediente::Manual => 2,
            };

            $cmp = $prioridad($a) <=> $prioridad($b);
            if (0 !== $cmp) {
                return $cmp;
            }

            $cmp = $a->orden() <=> $b->orden();
            if (0 !== $cmp) {
                return $cmp;
            }

            return $a->createdAt() <=> $b->createdAt();
        });

        return $grupo[0];
    }

    private function puedeEliminar(?ExpedienteDocumentoEntregado $entrega): bool
    {
        if (null === $entrega) {
            return true;
        }

        if ('' !== $entrega->archivoPath()) {
            return false;
        }

        return EstadoDocumentoEntregado::Pendiente === $entrega->estado();
    }
}
