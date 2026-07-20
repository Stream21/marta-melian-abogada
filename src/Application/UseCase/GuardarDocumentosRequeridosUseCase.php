<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\GuardarDocumentosRequeridosInput;
use App\Domain\Entity\FaseDocumentoTramite;
use App\Domain\Entity\TipoDocumentoRequerido;
use App\Domain\Entity\TramiteDocumentoRequerido;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use App\Domain\ValueObject\TramiteId;

final class GuardarDocumentosRequeridosUseCase
{
    /** Formato de entrega en plataformas; los uploads se convierten a PDF en runtime. */
    private const FORMATO_ENTREGA = ['pdf'];
    private const MAX_IMAGENES_CONJUNTO = 50;

    public function __construct(
        private TramiteDocumentoRequeridoRepositoryInterface $repository,
        private TramiteRepositoryInterface $tramiteRepository,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(GuardarDocumentosRequeridosInput $input): array
    {
        $tramiteId = new TramiteId($input->tramiteId);
        if (null === $this->tramiteRepository->findById($tramiteId)) {
            throw new \InvalidArgumentException('Trámite no encontrado.');
        }

        $documentos = $this->validateDocumentos($tramiteId, $input->documentos);
        $this->repository->replaceForTramite($tramiteId, $documentos);

        return array_map($this->serializeDocumento(...), $documentos);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDocumento(TramiteDocumentoRequerido $doc): array
    {
        return [
            'id' => $doc->id()->value(),
            'fase' => $doc->fase()->value,
            'nombre' => $doc->nombre(),
            'descripcion' => $doc->descripcion(),
            'obligatorio' => $doc->obligatorio(),
            'tipo' => $doc->tipo()->value,
            'maxImagenes' => $doc->maxImagenes(),
            'orden' => $doc->orden(),
            'formatoEntrega' => 'pdf',
        ];
    }

    /**
     * @param list<array<string, mixed>> $raw
     *
     * @return list<TramiteDocumentoRequerido>
     */
    private function validateDocumentos(TramiteId $tramiteId, array $raw): array
    {
        $documentos = [];

        foreach ($raw as $index => $item) {
            if (!is_array($item)) {
                throw new \InvalidArgumentException(sprintf('Documento %d no válido.', $index));
            }

            $nombre = trim((string) ($item['nombre'] ?? ''));
            if ('' === $nombre) {
                throw new \InvalidArgumentException(sprintf('El documento %d debe tener nombre.', $index));
            }

            $fase = FaseDocumentoTramite::fromValue($item['fase'] ?? FaseDocumentoTramite::DocumentosCliente->value);

            $tipo = TipoDocumentoRequerido::fromString((string) ($item['tipo'] ?? 'individual'));
            $maxImagenes = $this->resolveMaxImagenes($tipo, $item['maxImagenes'] ?? null, $index);

            $idRaw = $item['id'] ?? null;
            $id = is_string($idRaw) && '' !== trim($idRaw)
                ? new TramiteDocumentoRequeridoId($idRaw)
                : TramiteDocumentoRequeridoId::generate();

            $documentos[] = new TramiteDocumentoRequerido(
                $id,
                $tramiteId,
                $fase,
                $nombre,
                trim((string) ($item['descripcion'] ?? '')),
                (bool) ($item['obligatorio'] ?? true),
                $tipo,
                $maxImagenes,
                self::FORMATO_ENTREGA,
                isset($item['orden']) ? (int) $item['orden'] : $index,
            );
        }

        return $documentos;
    }

    private function resolveMaxImagenes(TipoDocumentoRequerido $tipo, mixed $raw, int $index): int
    {
        if (TipoDocumentoRequerido::Individual === $tipo) {
            return 1;
        }

        if (!is_numeric($raw)) {
            throw new \InvalidArgumentException(
                sprintf('Indique cuántos archivos puede anexar el cliente en el documento %d.', $index),
            );
        }

        $maxImagenes = (int) $raw;
        if ($maxImagenes < 2) {
            throw new \InvalidArgumentException(
                sprintf('El conjunto del documento %d debe permitir al menos 2 archivos.', $index),
            );
        }

        if ($maxImagenes > self::MAX_IMAGENES_CONJUNTO) {
            throw new \InvalidArgumentException(
                sprintf(
                    'El documento %d no puede superar %d archivos.',
                    $index,
                    self::MAX_IMAGENES_CONJUNTO,
                ),
            );
        }

        return $maxImagenes;
    }
}
