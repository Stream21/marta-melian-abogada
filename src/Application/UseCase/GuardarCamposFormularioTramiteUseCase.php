<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\TipoCampoFormulario;
use App\Domain\Entity\TramiteCampoFormulario;
use App\Domain\Repository\TramiteCampoFormularioRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteCampoFormularioId;
use App\Domain\ValueObject\TramiteId;

final class GuardarCamposFormularioTramiteUseCase
{
    public function __construct(
        private TramiteCampoFormularioRepositoryInterface $repository,
        private TramiteRepositoryInterface $tramiteRepository,
        private ListarCamposFormularioTramiteUseCase $listar,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $raw
     *
     * @return list<array<string, mixed>>
     */
    public function __invoke(string $tramiteId, array $raw): array
    {
        $id = new TramiteId($tramiteId);
        if (null === $this->tramiteRepository->findById($id)) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $campos = [];
        foreach ($raw as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException(sprintf('Campo %d no válido.', $index));
            }
            $clave = trim((string) ($item['clave'] ?? ''));
            $etiqueta = trim((string) ($item['etiqueta'] ?? ''));
            if ('' === $clave || '' === $etiqueta) {
                throw new \InvalidArgumentException(sprintf('El campo %d debe tener clave y etiqueta.', $index));
            }
            $opciones = $item['opciones'] ?? null;
            if (!is_array($opciones)) {
                $opciones = null;
            }
            $idRaw = $item['id'] ?? null;
            $campoId = is_string($idRaw) && '' !== trim($idRaw)
                ? new TramiteCampoFormularioId($idRaw)
                : TramiteCampoFormularioId::generate();

            $campos[] = new TramiteCampoFormulario(
                $campoId,
                $id,
                $clave,
                $etiqueta,
                TipoCampoFormulario::fromString((string) ($item['tipo'] ?? 'text')),
                (bool) ($item['obligatorio'] ?? true),
                (int) ($item['orden'] ?? $index),
                $opciones,
            );
        }

        $this->repository->replaceForTramite($id, $campos);

        return ($this->listar)($tramiteId);
    }
}
