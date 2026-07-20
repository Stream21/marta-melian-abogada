<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Repository\ExpedienteDocumentoArchivoRepositoryInterface;

final class DocumentoEntregaArchivoResolver
{
    public function __construct(
        private ExpedienteDocumentoArchivoRepositoryInterface $archivoRepository,
    ) {
    }

    public function resolveRelativePath(ExpedienteDocumentoEntregado $entregado, ?string $archivoId = null): string
    {
        $archivos = $this->archivoRepository->findByEntregadoId($entregado->id());

        if (null !== $archivoId && '' !== trim($archivoId)) {
            foreach ($archivos as $archivo) {
                if ($archivo->id() === $archivoId) {
                    return $archivo->archivoPath();
                }
            }

            throw new \InvalidArgumentException('Archivo no encontrado.');
        }

        if ([] !== $archivos) {
            return $archivos[0]->archivoPath();
        }

        if ('' === trim($entregado->archivoPath())) {
            throw new \InvalidArgumentException('Documento no encontrado.');
        }

        return $entregado->archivoPath();
    }
}
