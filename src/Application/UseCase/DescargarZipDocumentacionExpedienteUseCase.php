<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\ClienteFileStoragePort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\ExpedienteDocumentoEntregado;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoArchivoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\TramiteDocumentoRequeridoRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use App\Domain\ValueObject\TramiteId;

/**
 * Empaqueta documentos seleccionados en un ZIP con nombres legibles para el abogado:
 *   {numeroExpediente} - {nombreRequerimiento}[ - {parte}][ - {detalle}].{ext}
 */
final class DescargarZipDocumentacionExpedienteUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteDocumentoArchivoRepositoryInterface $archivoRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoExpedienteRepository,
        private TramiteDocumentoRequeridoRepositoryInterface $documentoRequeridoTramiteRepository,
        private ExpedienteFirmaRepositoryInterface $firmaRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private ClienteFileStoragePort $clienteFileStorage,
    ) {
    }

    /**
     * @param list<string> $itemIds
     *
     * @return array{content: string, filename: string}
     */
    public function __invoke(string $expedienteId, array $itemIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($v) => trim((string) $v), $itemIds),
            static fn (string $v) => '' !== $v,
        )));

        if ([] === $ids) {
            throw new \InvalidArgumentException('Seleccione al menos un documento.');
        }

        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        $nombresRequerimiento = $this->cargarNombresRequerimiento($expediente);
        $expedienteLabel = $this->etiquetaExpediente($expediente);

        $entries = [];
        foreach ($ids as $itemId) {
            foreach ($this->resolverArchivos($expediente, $expedienteLabel, $nombresRequerimiento, $itemId) as $entry) {
                $entries[] = $entry;
            }
        }

        if ([] === $entries) {
            throw new \InvalidArgumentException('Ninguno de los documentos seleccionados tiene archivo descargable.');
        }

        $tmpZip = tempnam(sys_get_temp_dir(), 'doczip_');
        if (false === $tmpZip) {
            throw new \RuntimeException('No se pudo crear el archivo temporal del ZIP.');
        }
        $zipPath = $tmpZip . '.zip';
        @unlink($tmpZip);

        $zip = new \ZipArchive();
        if (true !== $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            throw new \RuntimeException('No se pudo crear el ZIP.');
        }

        $usedNames = [];
        $closed = false;
        try {
            foreach ($entries as $entry) {
                $name = $this->uniqueName($entry['name'], $usedNames);
                $usedNames[$name] = true;
                if (!$zip->addFile($entry['path'], $name)) {
                    throw new \RuntimeException(sprintf('No se pudo añadir «%s» al ZIP.', $entry['name']));
                }
            }
            if (!$zip->close()) {
                throw new \RuntimeException('No se pudo cerrar el ZIP.');
            }
            $closed = true;

            $content = file_get_contents($zipPath);
            if (false === $content) {
                throw new \RuntimeException('No se pudo leer el ZIP generado.');
            }
        } finally {
            if (!$closed) {
                @$zip->close();
            }
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
        }

        $filename = sprintf('%s - documentacion.zip', $expedienteLabel);

        return [
            'content' => $content,
            'filename' => $filename,
        ];
    }

    /**
     * @return array<string, string> id documento requerido => nombre
     */
    private function cargarNombresRequerimiento(Expediente $expediente): array
    {
        $map = [];

        if (null !== $expediente->tramiteId() && '' !== $expediente->tramiteId()) {
            foreach ($this->documentoRequeridoTramiteRepository->findByTramiteId(new TramiteId($expediente->tramiteId())) as $doc) {
                $map[$doc->id()->value()] = $doc->nombre();
            }
        }

        foreach ($this->documentoRequeridoExpedienteRepository->findByExpediente($expediente->id()) as $doc) {
            $map[$doc->id()->value()] = $doc->nombre();
        }

        return $map;
    }

    /**
     * Prefijo legible: número de expediente (y cliente si aporta contexto).
     */
    private function etiquetaExpediente(Expediente $expediente): string
    {
        $numero = $this->sanitizeSegment($expediente->numero());
        if ('' === $numero) {
            $numero = 'Expediente';
        }

        $cliente = $this->sanitizeSegment($expediente->clientName());
        if ('' !== $cliente && !$this->contieneTexto($numero, $cliente)) {
            return $numero . ' - ' . $cliente;
        }

        return $numero;
    }

    /**
     * @param array<string, string> $nombresRequerimiento
     *
     * @return list<array{path: string, name: string}>
     */
    private function resolverArchivos(
        Expediente $expediente,
        string $expedienteLabel,
        array $nombresRequerimiento,
        string $itemId,
    ): array {
        if (str_starts_with($itemId, 'identidad-')) {
            return $this->resolverIdentidad($expediente->clienteId(), $expedienteLabel, $itemId);
        }

        if (str_starts_with($itemId, 'firma-')) {
            return $this->resolverFirma($expediente->id()->value(), $expedienteLabel, substr($itemId, 6));
        }

        return $this->resolverDocumentoEntregado(
            $expediente->id()->value(),
            $expedienteLabel,
            $nombresRequerimiento,
            $itemId,
        );
    }

    /**
     * @return list<array{path: string, name: string}>
     */
    private function resolverIdentidad(?string $clienteId, string $expedienteLabel, string $itemId): array
    {
        if (null === $clienteId || '' === $clienteId) {
            throw new \InvalidArgumentException('El expediente no tiene cliente vinculado.');
        }

        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            throw new \InvalidArgumentException('Cliente no encontrado.');
        }

        $lado = str_ends_with($itemId, 'reverso') ? 'reverso' : 'anverso';
        $relative = 'anverso' === $lado
            ? $cliente->documentoIdentidadAnversoPath()
            : $cliente->documentoIdentidadReversoPath();

        if (null === $relative || '' === $relative) {
            throw new \InvalidArgumentException(sprintf('No hay imagen de identidad (%s).', $lado));
        }

        $absolute = $this->clienteFileStorage->resolveAbsolutePath($relative);
        if (!is_file($absolute)) {
            throw new \InvalidArgumentException(sprintf('Archivo de identidad (%s) no disponible.', $lado));
        }

        $detalle = 'anverso' === $lado ? 'Delantera' : 'Trasera';

        return [[
            'path' => $absolute,
            'name' => $this->buildFilename(
                $expedienteLabel,
                'Documento de identidad',
                $absolute,
                $detalle,
            ),
        ]];
    }

    /**
     * @return list<array{path: string, name: string}>
     */
    private function resolverFirma(string $expedienteId, string $expedienteLabel, string $tipoValue): array
    {
        $tipo = TipoEscrito::fromString($tipoValue);
        $firma = $this->firmaRepository->findByExpedienteAndTipo(
            new ExpedienteId($expedienteId),
            $tipo,
        );

        if (null === $firma || null === $firma->pdfFirmadoPath() || '' === $firma->pdfFirmadoPath()) {
            throw new \InvalidArgumentException(sprintf('No hay PDF firmado de «%s».', $tipo->label()));
        }

        $absolute = $this->fileStorage->getAbsolutePath($firma->pdfFirmadoPath());
        if (!is_file($absolute)) {
            throw new \InvalidArgumentException(sprintf('El PDF firmado de «%s» no está disponible.', $tipo->label()));
        }

        return [[
            'path' => $absolute,
            'name' => $this->buildFilename(
                $expedienteLabel,
                $tipo->label() . ' (firmado)',
                $absolute,
            ),
        ]];
    }

    /**
     * @param array<string, string> $nombresRequerimiento
     *
     * @return list<array{path: string, name: string}>
     */
    private function resolverDocumentoEntregado(
        string $expedienteId,
        string $expedienteLabel,
        array $nombresRequerimiento,
        string $docId,
    ): array {
        $expId = new ExpedienteId($expedienteId);
        $entregado = $this->documentoEntregadoRepository->findByExpedienteAndDocumento(
            $expId,
            new TramiteDocumentoRequeridoId($docId),
        );

        if (null === $entregado) {
            $entregado = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento(
                $expId,
                new ExpedienteDocumentoRequeridoId($docId),
            );
        }

        if (null === $entregado) {
            throw new \InvalidArgumentException(sprintf('Documento no encontrado: %s.', $docId));
        }

        $requerimiento = $nombresRequerimiento[$docId]
            ?? $this->resolverNombreRequerimientoDesdeEntrega($entregado)
            ?? 'Documento';

        $archivos = $this->archivoRepository->findByEntregadoId($entregado->id());
        if ([] !== $archivos) {
            $disponibles = [];
            foreach ($archivos as $archivo) {
                $absolute = $this->fileStorage->getAbsolutePath($archivo->archivoPath());
                if (!is_file($absolute)) {
                    continue;
                }
                $disponibles[] = [
                    'path' => $absolute,
                    'original' => $archivo->nombreOriginal(),
                ];
            }

            if ([] !== $disponibles) {
                $total = count($disponibles);
                $entries = [];
                foreach ($disponibles as $i => $file) {
                    $entries[] = [
                        'path' => $file['path'],
                        'name' => $this->buildFilename(
                            $expedienteLabel,
                            $requerimiento,
                            $file['path'],
                            $this->sufijoParte($file['original'], $i + 1, $total),
                        ),
                    ];
                }

                return $entries;
            }
        }

        $path = $entregado->archivoPath();
        if ('' === trim($path)) {
            throw new \InvalidArgumentException('El documento seleccionado no tiene archivo adjunto.');
        }

        $absolute = $this->fileStorage->getAbsolutePath($path);
        if (!is_file($absolute)) {
            throw new \InvalidArgumentException('El archivo del documento no está disponible.');
        }

        return [[
            'path' => $absolute,
            'name' => $this->buildFilename($expedienteLabel, $requerimiento, $absolute),
        ]];
    }

    private function resolverNombreRequerimientoDesdeEntrega(
        ExpedienteDocumentoEntregado $entregado,
    ): ?string {
        $expDocId = $entregado->expedienteDocumentoRequeridoId();
        if (null !== $expDocId) {
            $doc = $this->documentoRequeridoExpedienteRepository->findById($expDocId);

            return $doc?->nombre();
        }

        return null;
    }

    /**
     * Construye: "{expediente} - {requerimiento}[ - {parte}].{ext}"
     */
    private function buildFilename(
        string $expedienteLabel,
        string $requerimiento,
        string $absolutePath,
        ?string $parte = null,
    ): string {
        $ext = strtolower(pathinfo($absolutePath, \PATHINFO_EXTENSION) ?: 'bin');
        $req = $this->sanitizeSegment($requerimiento);
        if ('' === $req) {
            $req = 'Documento';
        }

        $stem = $expedienteLabel . ' - ' . $req;
        if (null !== $parte && '' !== trim($parte)) {
            $parteSafe = $this->sanitizeSegment($parte);
            if ('' !== $parteSafe) {
                $stem .= ' - ' . $parteSafe;
            }
        }

        if (mb_strlen($stem) > 150) {
            $stem = rtrim(mb_substr($stem, 0, 150), " .\t-");
        }

        return $stem . '.' . $ext;
    }

    /**
     * Si hay varios archivos del mismo requerimiento:
     *   01
     *   01 - nomina_enero   (si el nombre original aporta información)
     */
    private function sufijoParte(string $nombreOriginal, int $indice, int $total): ?string
    {
        if ($total <= 1) {
            return null;
        }

        $indiceLabel = sprintf('%02d', $indice);
        $stem = pathinfo($nombreOriginal, \PATHINFO_FILENAME);
        $stemSafe = $this->sanitizeSegment($stem);

        if ('' === $stemSafe || $this->pareceIdentificadorOpaco($stemSafe)) {
            return $indiceLabel;
        }

        if ($this->contieneTexto($stemSafe, $indiceLabel)) {
            return $stemSafe;
        }

        return $indiceLabel . ' - ' . $stemSafe;
    }

    private function pareceIdentificadorOpaco(string $value): bool
    {
        $compact = str_replace(['-', '_'], '', $value);
        if (preg_match('/^[0-9a-f]{8,}$/i', $compact)) {
            return true;
        }

        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }

    private function contieneTexto(string $haystack, string $needle): bool
    {
        return '' !== $needle && false !== mb_stripos($haystack, $needle);
    }

    /**
     * @param array<string, true> $usedNames
     */
    private function uniqueName(string $name, array $usedNames): string
    {
        if (!isset($usedNames[$name])) {
            return $name;
        }

        $dot = strrpos($name, '.');
        $stem = false === $dot ? $name : substr($name, 0, $dot);
        $ext = false === $dot ? '' : substr($name, $dot);
        $i = 2;
        do {
            $candidate = sprintf('%s (%d)%s', $stem, $i, $ext);
            ++$i;
        } while (isset($usedNames[$candidate]));

        return $candidate;
    }

    /**
     * Segmento seguro para ZIP/Windows: sin rutas, control chars ni puntuación problemática.
     * Conserva letras (incl. acentos), números, espacios, guiones y puntos.
     */
    private function sanitizeSegment(string $name): string
    {
        $name = str_replace(["\0", '\\', '/', ':', '*', '?', '"', '<', '>', '|'], ' ', $name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? $name;
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);
        $name = trim($name, " .\t-");

        if (mb_strlen($name) > 80) {
            $name = rtrim(mb_substr($name, 0, 80), " .\t-");
        }

        return $name;
    }
}
