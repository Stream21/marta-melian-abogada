<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\ClienteFileStoragePort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\UseCase\DescargarZipDocumentacionExpedienteUseCase;
use App\Application\UseCase\ListarDocumentacionExpedienteUseCase;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use App\Infrastructure\Http\UploadedFileMimeDetector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/expedientes/{id}/documentacion', name: 'api_expedientes_documentacion_')]
#[IsGranted('ROLE_USER')]
final class ExpedienteDocumentacionController extends AbstractController
{
    public function __construct(
        private ListarDocumentacionExpedienteUseCase $listarDocumentacion,
        private DescargarZipDocumentacionExpedienteUseCase $descargarZip,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private ClienteFileStoragePort $clienteFileStorage,
        private UploadedFileMimeDetector $mimeDetector,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->listarDocumentacion)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/zip', name: 'zip', methods: ['POST'])]
    public function zip(string $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $itemIds = $data['itemIds'] ?? [];
        if (!is_array($itemIds)) {
            return new JsonResponse(['message' => 'itemIds debe ser un array.'], Response::HTTP_BAD_REQUEST);
        }

        $itemIds = array_values(array_map(static fn ($v) => (string) $v, $itemIds));

        try {
            $result = ($this->descargarZip)($id, $itemIds);

            return new Response($result['content'], Response::HTTP_OK, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => $this->contentDispositionAttachment($result['filename']),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/identidad/{lado}', name: 'identidad', methods: ['GET'], requirements: ['lado' => 'anverso|reverso'])]
    public function identidad(string $id, string $lado): Response
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($id));
        if (null === $expediente || null === $expediente->clienteId() || '' === $expediente->clienteId()) {
            return new JsonResponse(['message' => 'Expediente o cliente no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $cliente = $this->clienteRepository->findById(new ClienteId($expediente->clienteId()));
        if (null === $cliente) {
            return new JsonResponse(['message' => 'Cliente no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $relative = 'anverso' === $lado
            ? $cliente->documentoIdentidadAnversoPath()
            : $cliente->documentoIdentidadReversoPath();

        if (null === $relative || '' === $relative) {
            return new JsonResponse(['message' => 'Imagen no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $absolute = $this->clienteFileStorage->resolveAbsolutePath($relative);
        if (!is_file($absolute)) {
            return new JsonResponse(['message' => 'Archivo no disponible.'], Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($absolute);
        $response->headers->set('Content-Type', $this->mimeDetector->detectFromPath($absolute));
        $response->headers->set('Content-Disposition', 'inline; filename="documento-identidad-' . $lado . '.jpg"');

        return $response;
    }

    #[Route(path: '/{docId}/archivo', name: 'archivo', methods: ['GET'])]
    public function archivo(string $id, string $docId): Response
    {
        $expedienteId = new ExpedienteId($id);
        $expediente = $this->expedienteRepository->findById($expedienteId);
        if (null === $expediente) {
            return new JsonResponse(['message' => 'Expediente no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $entregado = $this->documentoEntregadoRepository->findByExpedienteAndDocumento(
            $expedienteId,
            new TramiteDocumentoRequeridoId($docId),
        );

        if (null === $entregado) {
            $entregado = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento(
                $expedienteId,
                new ExpedienteDocumentoRequeridoId($docId),
            );
        }

        if (null === $entregado) {
            return new JsonResponse(['message' => 'Documento no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $content = $this->fileStorage->readRelativePath($entregado->archivoPath());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="documento.pdf"',
        ]);
    }

    private function contentDispositionAttachment(string $filename): string
    {
        $ascii = preg_replace('/[^\x20-\x7E]/', '_', $filename) ?? 'documentacion.zip';
        $ascii = str_replace(['"', '\\'], '_', $ascii);

        return sprintf(
            "attachment; filename=\"%s\"; filename*=UTF-8''%s",
            $ascii,
            rawurlencode($filename),
        );
    }
}
