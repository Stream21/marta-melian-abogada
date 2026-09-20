<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\RequerimientoMercurioCampoOpcionesNormalizer;
use App\Domain\Entity\ExpedienteRequerimientoCampo;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\TipoCampoFormulario;
use App\Domain\Entity\TipoRequerimientoMercurio;
use App\Domain\Repository\ExpedienteRequerimientoCampoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoCampoId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;

final class GestionarCamposRequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteRequerimientoCampoRepositoryInterface $campoRepository,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $campos
     */
    public function __invoke(
        string $expedienteId,
        string $requerimientoId,
        array $campos,
        ?string $formularioNombre = null,
        ?string $formularioCometido = null,
        bool $actualizarMetaFormulario = false,
    ): void
    {
        $expId = new ExpedienteId($expedienteId);
        $expediente = $this->expedienteRepository->findById($expId);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }
        if (FaseNegocioExpediente::Tramitacion !== $expediente->faseNegocio()) {
            throw new \InvalidArgumentException('El expediente no está en fase de tramitación.');
        }

        $reqId = new ExpedienteRequerimientoMercurioId($requerimientoId);
        $req = $this->requerimientoRepository->findById($reqId);
        if (null === $req || !$req->expedienteId()->equals($expId)) {
            throw new \InvalidArgumentException('Requerimiento no encontrado.');
        }
        if (!$req->estado()->estaAbierto()) {
            throw new \InvalidArgumentException('El requerimiento ya está cerrado o presentado.');
        }
        if (TipoRequerimientoMercurio::Tasas === $req->tipo()) {
            throw new \InvalidArgumentException('El requerimiento de tasas no admite formulario. Use el documento de tasa.');
        }

        $existentes = $this->campoRepository->findByRequerimientoId($reqId);
        /** @var array<string, ExpedienteRequerimientoCampo> $existentesPorId */
        $existentesPorId = [];
        foreach ($existentes as $campo) {
            $existentesPorId[$campo->id()->value()] = $campo;
        }

        $idsEnPayload = [];
        $toSave = [];

        foreach ($campos as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('Formato de campo no válido.');
            }

            $idStr = isset($item['id']) ? trim((string) $item['id']) : '';
            $clave = trim((string) ($item['clave'] ?? ''));
            $etiqueta = trim((string) ($item['etiqueta'] ?? ''));
            if ('' === $clave) {
                $clave = 'campo_' . ($index + 1);
            }
            if ('' === $etiqueta) {
                throw new \InvalidArgumentException('Cada campo debe tener etiqueta.');
            }
            $tipo = TipoCampoFormulario::fromString((string) ($item['tipo'] ?? 'text'));
            $opcionesNormalizadas = RequerimientoMercurioCampoOpcionesNormalizer::normalizar(
                $item['opciones'] ?? null,
                $tipo,
            );
            $obligatorio = (bool) ($item['obligatorio'] ?? true);
            $orden = (int) ($item['orden'] ?? $index);

            if ('' !== $idStr) {
                if (!isset($existentesPorId[$idStr])) {
                    throw new \InvalidArgumentException('Campo de requerimiento no encontrado.');
                }
                if (isset($idsEnPayload[$idStr])) {
                    throw new \InvalidArgumentException('Identificador de campo duplicado en la petición.');
                }
                $idsEnPayload[$idStr] = true;
                $toSave[] = $existentesPorId[$idStr]->withDefinicion(
                    $clave,
                    $etiqueta,
                    $tipo,
                    $obligatorio,
                    $orden,
                    $opcionesNormalizadas,
                );
                continue;
            }

            $toSave[] = ExpedienteRequerimientoCampo::crear(
                ExpedienteRequerimientoCampoId::generate(),
                $reqId,
                $clave,
                $etiqueta,
                $tipo,
                $obligatorio,
                $orden,
                $opcionesNormalizadas,
            );
        }

        foreach ($existentes as $campo) {
            if (!isset($idsEnPayload[$campo->id()->value()])) {
                $this->campoRepository->delete($campo->id());
            }
        }

        if ([] !== $toSave) {
            $this->campoRepository->saveAll($toSave);
        }

        if ($actualizarMetaFormulario) {
            if ([] === $toSave && [] === $campos) {
                $req = $req->withFormularioMeta(null, null);
            } else {
                $req = $req->withFormularioMeta($formularioNombre, $formularioCometido);
            }
        }

        $req = $req->abrirParaCliente();
        $this->requerimientoRepository->save($req);
    }
}
