<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\ServicioCampoFormulario;
use App\Domain\Entity\TipoCampoFormulario;
use App\Domain\Repository\ServicioCampoFormularioRepositoryInterface;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\ValueObject\ServicioCampoFormularioId;
use App\Domain\ValueObject\ServicioId;

final class GuardarCamposFormularioServicioUseCase
{
    public function __construct(
        private ServicioCampoFormularioRepositoryInterface $repository,
        private ServicioRepositoryInterface $servicioRepository,
        private ListarCamposFormularioServicioUseCase $listar,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $raw
     *
     * @return list<array<string, mixed>>
     */
    public function __invoke(string $servicioId, array $raw): array
    {
        $id = new ServicioId($servicioId);
        if (null === $this->servicioRepository->findById($id)) {
            throw new \InvalidArgumentException('Servicio no encontrado.');
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
                ? new ServicioCampoFormularioId($idRaw)
                : ServicioCampoFormularioId::generate();

            $campos[] = new ServicioCampoFormulario(
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

        $this->repository->replaceForServicio($id, $campos);

        return ($this->listar)($servicioId);
    }
}
