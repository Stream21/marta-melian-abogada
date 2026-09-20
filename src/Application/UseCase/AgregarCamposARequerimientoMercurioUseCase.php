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

final class AgregarCamposARequerimientoMercurioUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteRequerimientoCampoRepositoryInterface $campoRepository,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $campos
     *
     * @return list<string> ids creados
     */
    public function __invoke(string $expedienteId, string $requerimientoId, array $campos): array
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

        if ([] === $campos) {
            throw new \InvalidArgumentException('Debe indicar al menos un campo.');
        }

        $existentes = $this->campoRepository->findByRequerimientoId($reqId);
        $ordenBase = count($existentes);
        $domain = [];
        $ids = [];

        foreach ($campos as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException('Formato de campo no válido.');
            }
            $clave = trim((string) ($item['clave'] ?? ''));
            $etiqueta = trim((string) ($item['etiqueta'] ?? ''));
            if ('' === $clave) {
                $clave = 'campo_' . ($ordenBase + $index + 1);
            }
            if ('' === $etiqueta) {
                throw new \InvalidArgumentException('Cada campo debe tener etiqueta.');
            }
            $tipo = TipoCampoFormulario::fromString((string) ($item['tipo'] ?? 'text'));
            $opcionesNormalizadas = RequerimientoMercurioCampoOpcionesNormalizer::normalizar(
                $item['opciones'] ?? null,
                $tipo,
            );

            $campoId = ExpedienteRequerimientoCampoId::generate();
            $domain[] = ExpedienteRequerimientoCampo::crear(
                $campoId,
                $reqId,
                $clave,
                $etiqueta,
                $tipo,
                (bool) ($item['obligatorio'] ?? true),
                (int) ($item['orden'] ?? $ordenBase + $index),
                $opcionesNormalizadas,
            );
            $ids[] = $campoId->value();
        }

        $this->campoRepository->saveAll($domain);

        $req = $req->abrirParaCliente();
        $this->requerimientoRepository->save($req);

        return $ids;
    }
}
