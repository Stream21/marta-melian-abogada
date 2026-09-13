<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\NotificarTramitacionClienteService;
use App\Application\Service\TramitacionSubfaseSyncService;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\DestinoRequerimientoMercurio;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\ExpedienteRequerimientoCampo;
use App\Domain\Entity\ExpedienteRequerimientoDocumento;
use App\Domain\Entity\ExpedienteRequerimientoMercurio;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\ResponsableRequerimientoDocumento;
use App\Domain\Entity\TipoCampoFormulario;
use App\Domain\Entity\TipoRequerimientoMercurio;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoCampoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\ServicioCampoFormularioRepositoryInterface;
use App\Domain\Repository\TramiteCampoFormularioRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoCampoId;
use App\Domain\ValueObject\ExpedienteRequerimientoDocumentoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;
use App\Domain\ValueObject\ServicioId;
use App\Domain\ValueObject\TramiteId;

final class AgregarRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteRequerimientoDocumentoRepositoryInterface $documentoRepository,
        private ExpedienteRequerimientoCampoRepositoryInterface $campoRepository,
        private ServicioCampoFormularioRepositoryInterface $servicioCampoRepository,
        private TramiteCampoFormularioRepositoryInterface $tramiteCampoRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private NotificarTramitacionClienteService $notificar,
        private TramitacionSubfaseSyncService $subfaseSync,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $documentos
     * @param list<array<string, mixed>> $campos
     */
    public function __invoke(
        string $expedienteId,
        string $tipo,
        string $destino,
        string $nombre,
        string $descripcion = '',
        array $documentos = [],
        array $campos = [],
        ?string $plantillaFrom = null,
    ): string {
        $id = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($id);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        if ($this->requerimientoRepository->countAbiertosByExpediente($id) > 0) {
            throw new \InvalidArgumentException('Solo puede haber un requerimiento Mercurio activo a la vez.');
        }

        if (null !== $plantillaFrom && '' !== $plantillaFrom) {
            $campos = array_merge($campos, $this->camposDesdePlantilla($expediente, $plantillaFrom));
        }

        // Permite dar de alta solo con nombre; los entregables se configuran después.

        $reqId = new ExpedienteRequerimientoMercurioId(bin2hex(random_bytes(16)));
        $requerimiento = ExpedienteRequerimientoMercurio::crear(
            $reqId,
            $id,
            TipoRequerimientoMercurio::fromString($tipo),
            DestinoRequerimientoMercurio::fromString($destino),
            $nombre,
            $descripcion,
        );
        $this->requerimientoRepository->save($requerimiento);

        $docsDomain = [];
        foreach ($documentos as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('Formato de documento no válido.');
            }
            $nombreDoc = trim((string) ($item['nombre'] ?? ''));
            if ('' === $nombreDoc) {
                throw new \InvalidArgumentException('Cada documento debe tener nombre.');
            }
            $docsDomain[] = ExpedienteRequerimientoDocumento::crear(
                ExpedienteRequerimientoDocumentoId::generate(),
                $reqId,
                $nombreDoc,
                trim((string) ($item['descripcion'] ?? '')),
                ResponsableRequerimientoDocumento::fromString((string) ($item['responsable'] ?? 'abogado')),
                (bool) ($item['obligatorio'] ?? true),
                (int) ($item['orden'] ?? $index),
                (int) ($item['maxArchivos'] ?? $item['numeroArchivos'] ?? 1),
            );
        }
        if ([] !== $docsDomain) {
            $this->documentoRepository->saveAll($docsDomain);
        }

        $camposDomain = [];
        foreach ($campos as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('Formato de campo no válido.');
            }
            $clave = trim((string) ($item['clave'] ?? ''));
            $etiqueta = trim((string) ($item['etiqueta'] ?? ''));
            if ('' === $clave || '' === $etiqueta) {
                throw new \InvalidArgumentException('Cada campo debe tener clave y etiqueta.');
            }
            $opciones = $item['opciones'] ?? null;
            if (!is_array($opciones)) {
                $opciones = null;
            }
            $camposDomain[] = ExpedienteRequerimientoCampo::crear(
                ExpedienteRequerimientoCampoId::generate(),
                $reqId,
                $clave,
                $etiqueta,
                TipoCampoFormulario::fromString((string) ($item['tipo'] ?? 'text')),
                (bool) ($item['obligatorio'] ?? true),
                (int) ($item['orden'] ?? $index),
                $opciones,
            );
        }
        if ([] !== $camposDomain) {
            $this->campoRepository->saveAll($camposDomain);
        }

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $id,
            'requerimiento_mercurio_anadido',
            sprintf('Requerimiento Mercurio añadido: %s (%s).', $requerimiento->nombre(), $requerimiento->destino()->label()),
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
        ));

        $this->subfaseSync->sync($expediente);

        if (
            DestinoRequerimientoMercurio::Cliente === $requerimiento->destino()
            && null !== $expediente->clienteId()
            && '' !== $expediente->clienteId()
        ) {
            $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
            if (null !== $cliente) {
                $this->notificar->notificarRequerimientoCliente(
                    $expediente,
                    $cliente,
                    $requerimiento->nombre(),
                    $descripcion,
                );
            }
        }

        return $reqId->value();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function camposDesdePlantilla(\App\Domain\Entity\Expediente $expediente, string $plantillaFrom): array
    {
        $out = [];
        if ('servicio' === $plantillaFrom) {
            $sid = $expediente->servicioId();
            if (null === $sid || '' === $sid) {
                throw new \InvalidArgumentException('El expediente no tiene servicio asociado.');
            }
            foreach ($this->servicioCampoRepository->findByServicioId(new ServicioId($sid)) as $campo) {
                $out[] = [
                    'clave' => $campo->clave(),
                    'etiqueta' => $campo->etiqueta(),
                    'tipo' => $campo->tipo()->value,
                    'opciones' => $campo->opcionesJson(),
                    'obligatorio' => $campo->obligatorio(),
                    'orden' => $campo->orden(),
                ];
            }

            return $out;
        }
        if ('tramite' === $plantillaFrom) {
            $tid = $expediente->tramiteId();
            if (null === $tid || '' === $tid) {
                throw new \InvalidArgumentException('El expediente no tiene trámite asociado.');
            }
            foreach ($this->tramiteCampoRepository->findByTramiteId(new TramiteId($tid)) as $campo) {
                $out[] = [
                    'clave' => $campo->clave(),
                    'etiqueta' => $campo->etiqueta(),
                    'tipo' => $campo->tipo()->value,
                    'opciones' => $campo->opcionesJson(),
                    'obligatorio' => $campo->obligatorio(),
                    'orden' => $campo->orden(),
                ];
            }

            return $out;
        }

        throw new \InvalidArgumentException('plantillaFrom debe ser servicio o tramite.');
    }
}
