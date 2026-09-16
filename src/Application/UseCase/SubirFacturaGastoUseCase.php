<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\GastoFileStoragePort;
use App\Application\Service\DocumentoUploadNormalizer;
use App\Domain\Entity\Gasto;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\ValueObject\GastoId;

final class SubirFacturaGastoUseCase
{
    public function __construct(
        private GastoRepositoryInterface $gastoRepository,
        private GastoFileStoragePort $fileStorage,
        private DocumentoUploadNormalizer $uploadNormalizer,
    ) {
    }

    /**
     * @param list<array{content: string, mime: string, nombreOriginal?: string}> $archivos
     *
     * @return array<string, mixed>
     */
    public function __invoke(string $id, array $archivos): array
    {
        $gastoId = new GastoId($id);
        $gasto = $this->gastoRepository->findById($gastoId);
        if (null === $gasto) {
            throw new \InvalidArgumentException('Gasto no encontrado.');
        }

        if ([] === $archivos) {
            throw new \InvalidArgumentException('Debe adjuntar al menos un archivo.');
        }

        $normalizados = $this->uploadNormalizer->normalizarArchivosIndividuales(
            $archivos,
            TipoDocumentoRequerido::Individual,
            1,
        );
        $pdf = $normalizados[0] ?? null;
        if (null === $pdf) {
            throw new \InvalidArgumentException('No se pudo procesar el archivo de factura.');
        }

        if ($gasto->tieneFactura() && null !== $gasto->facturaPdfPath()) {
            $this->fileStorage->deleteRelativePath($gasto->facturaPdfPath());
        }

        $relativePath = $this->fileStorage->savePdf($gastoId, 'factura.pdf', $pdf['content']);
        $gasto = $gasto->withFacturaPdfPath($relativePath);
        $this->gastoRepository->save($gasto);

        return $this->toArray($gasto);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Gasto $gasto): array
    {
        return [
            'id' => $gasto->id()->value(),
            'concepto' => $gasto->concepto(),
            'importe' => $gasto->importe(),
            'fecha' => $gasto->fecha()->format('Y-m-d'),
            'categoria' => $gasto->categoria(),
            'notas' => $gasto->notas(),
            'tieneFactura' => $gasto->tieneFactura(),
            'facturaUrl' => $gasto->facturaUrl(),
            'createdAt' => $gasto->createdAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $gasto->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
