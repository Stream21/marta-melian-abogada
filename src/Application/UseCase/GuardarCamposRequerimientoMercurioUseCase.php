<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\RequerimientoMercurioCompletitudService;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoRequerimientoMercurio;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoCampoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoCampoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class GuardarCamposRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteRequerimientoCampoRepositoryInterface $campoRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private TramitacionSubfaseSyncService $subfaseSync,
        private RequerimientoMercurioCompletitudService $completitud,
    ) {
    }

    /**
     * @param list<array{id: string, valor?: string|null}> $valores
     */
    public function __invoke(
        string $expedienteId,
        string $requerimientoId,
        array $valores,
        bool $desdePortalCliente = false,
    ): void {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        $req = $this->requerimientoRepository->findById(new ExpedienteRequerimientoMercurioId($requerimientoId));
        if (null === $req || $req->expedienteId()->value() !== $id->value()) {
            throw new \InvalidArgumentException('Requerimiento no encontrado.');
        }

        if ($desdePortalCliente) {
            if (!$req->estado()->estaAbierto()) {
                throw new \InvalidArgumentException('El requerimiento ya no admite cambios por el cliente.');
            }
            // Formularios/docs de cliente pueden haberse creado sobre un req. «interno»;
            // abrirlo al portal antes de guardar.
            $abiertoCliente = $req->abrirParaCliente();
            if ($abiertoCliente !== $req) {
                $this->requerimientoRepository->save($abiertoCliente);
                $req = $abiertoCliente;
            }
            if (EstadoRequerimientoMercurio::PendienteCliente !== $req->estado()) {
                throw new \InvalidArgumentException('El requerimiento ya no admite cambios por el cliente.');
            }
        }

        $camposById = [];
        foreach ($this->campoRepository->findByRequerimientoId($req->id()) as $campo) {
            $camposById[$campo->id()->value()] = $campo;
        }

        foreach ($valores as $item) {
            if (!is_array($item)) {
                continue;
            }
            $campoId = (string) ($item['id'] ?? '');
            if ('' === $campoId || !isset($camposById[$campoId])) {
                throw new \InvalidArgumentException('Campo de formulario no válido.');
            }
            $campo = $camposById[$campoId];
            $valor = array_key_exists('valor', $item) ? $item['valor'] : null;
            $valorStr = null === $valor ? null : (string) $valor;
            $this->campoRepository->save($campo->withValor($valorStr));
        }

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'requerimiento_mercurio_campos',
            sprintf('Datos del formulario actualizados en «%s»%s.', $req->nombre(), $desdePortalCliente ? ' (cliente)' : ''),
            $desdePortalCliente ? ActorHitoExpediente::Cliente : ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->completitud->sincronizarEstadoTrasCambioCliente($req);
        $this->subfaseSync->sync($expediente);
    }
}
