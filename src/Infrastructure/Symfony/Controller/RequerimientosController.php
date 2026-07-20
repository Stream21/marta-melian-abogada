<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\UseCase\AgregarDocumentoRequerimientosUseCase;
use App\Application\UseCase\AsignarDocumentoRequerimientoAlAbogadoUseCase;
use App\Application\UseCase\AvanzarTramitacionUseCase;
use App\Application\UseCase\DerivarDocumentoRequerimientoAlClienteUseCase;
use App\Application\UseCase\DevolverDocumentoRequerimientosUseCase;
use App\Application\UseCase\GenerarPdfConjuntoRequerimientosUseCase;
use App\Application\UseCase\ObtenerRequerimientosExpedienteUseCase;
use App\Application\UseCase\SubirDocumentoRequerimientosAbogadoUseCase;
use App\Application\UseCase\ValidarDocumentoRequerimientosUseCase;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Application\Service\DocumentoEntregaArchivoResolver;
use App\Infrastructure\Http\SafeJsonResponse;
use App\Infrastructure\Http\Utf8Sanitizer;
use App\Infrastructure\Http\UploadedArchivosExtractor;
use App\Infrastructure\Http\UploadedFileMimeDetector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/expedientes/{id}/requerimientos', name: 'api_expedientes_requerimientos_')]
final class RequerimientosController extends AbstractController
{
    public function __construct(
        private ObtenerRequerimientosExpedienteUseCase $obtenerRequerimientos,
        private AgregarDocumentoRequerimientosUseCase $agregarDocumento,
        private ValidarDocumentoRequerimientosUseCase $validarDocumento,
        private DevolverDocumentoRequerimientosUseCase $devolverDocumento,
        private AsignarDocumentoRequerimientoAlAbogadoUseCase $asignarDocumentoAbogado,
        private DerivarDocumentoRequerimientoAlClienteUseCase $derivarDocumentoCliente,
        private SubirDocumentoRequerimientosAbogadoUseCase $subirDocumentoAbogado,
        private AvanzarTramitacionUseCase $avanzarTramitacion,
        private GenerarPdfConjuntoRequerimientosUseCase $generarPdfConjunto,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private UploadedFileMimeDetector $mimeDetector,
        private UploadedArchivosExtractor $archivosExtractor,
        private DocumentoEntregaArchivoResolver $archivoResolver,
    ) {
    }

    #[Route(path: '', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerRequerimientos)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/documentos', name: 'agregar_documento', methods: ['POST'])]
    public function agregarDocumento(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $docId = ($this->agregarDocumento)(
                $id,
                (string) ($data['nombre'] ?? ''),
                (string) ($data['descripcion'] ?? ''),
                (bool) ($data['obligatorio'] ?? true),
                (string) ($data['tipo'] ?? 'individual'),
                (int) ($data['maxImagenes'] ?? 1),
            );

            return new JsonResponse(['id' => $docId], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/documentos/{docId}/validar', name: 'validar_documento', methods: ['POST'])]
    public function validarDocumento(string $id, string $docId): JsonResponse
    {
        try {
            ($this->validarDocumento)($id, $docId);

            return new JsonResponse(($this->obtenerRequerimientos)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/documentos/{docId}/devolver', name: 'devolver_documento', methods: ['POST'])]
    public function devolverDocumento(string $id, string $docId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $nota = isset($data['nota']) ? (string) $data['nota'] : '';

        try {
            ($this->devolverDocumento)($id, $docId, $nota);

            return new JsonResponse(($this->obtenerRequerimientos)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/documentos/{docId}/asignar-abogado', name: 'asignar_documento_abogado', methods: ['POST'])]
    public function asignarDocumentoAbogado(string $id, string $docId): JsonResponse
    {
        try {
            ($this->asignarDocumentoAbogado)($id, $docId);

            return new JsonResponse(($this->obtenerRequerimientos)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/documentos/{docId}/derivar-cliente', name: 'derivar_documento_cliente', methods: ['POST'])]
    public function derivarDocumentoCliente(string $id, string $docId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $nota = isset($data['nota']) ? (string) $data['nota'] : '';

        try {
            ($this->derivarDocumentoCliente)($id, $docId, $nota);

            return new JsonResponse(($this->obtenerRequerimientos)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/documentos/{docId}/archivo', name: 'documento_archivo', methods: ['GET'])]
    public function documentoArchivo(string $id, string $docId, Request $request): Response
    {
        $expedienteId = new ExpedienteId($id);
        $entregado = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento(
            $expedienteId,
            new ExpedienteDocumentoRequeridoId($docId),
        );

        if (null === $entregado) {
            return new JsonResponse(['message' => 'Documento no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $relativePath = $this->archivoResolver->resolveRelativePath(
                $entregado,
                $request->query->get('archivoId'),
            );
            $content = $this->fileStorage->readRelativePath($relativePath);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="documento.pdf"',
        ]);
    }

    #[Route(path: '/documentos/{docId}/subir', name: 'subir_documento_abogado', methods: ['POST'])]
    public function subirDocumentoAbogado(string $id, string $docId, Request $request): JsonResponse
    {
        $archivos = $this->archivosExtractor->extract($request);
        if ([] === $archivos) {
            return new JsonResponse(['message' => 'Debe adjuntar al menos un archivo.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $modo = (string) $request->query->get('modo', 'validar');
            ($this->subirDocumentoAbogado)($id, $docId, $archivos, $modo);

            return new SafeJsonResponse(($this->obtenerRequerimientos)($id));
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return SafeJsonResponse::message($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $message = Utf8Sanitizer::sanitize($e->getMessage());
            if ('' === $message) {
                $message = 'No se pudo procesar la imagen. Compruebe que los archivos sean imágenes válidas.';
            }

            return SafeJsonResponse::message($message, Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/pdf-conjunto', name: 'pdf_conjunto', methods: ['POST'])]
    public function pdfConjunto(string $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $archivoIds = $data['archivoIds'] ?? [];
        $documentoIds = $data['documentoIds'] ?? [];
        if (!is_array($archivoIds)) {
            return new JsonResponse(['message' => 'archivoIds debe ser un array.'], Response::HTTP_BAD_REQUEST);
        }
        if (!is_array($documentoIds)) {
            return new JsonResponse(['message' => 'documentoIds debe ser un array.'], Response::HTTP_BAD_REQUEST);
        }

        $archivoIds = array_values(array_map(static fn ($v) => (string) $v, $archivoIds));
        $documentoIds = array_values(array_map(static fn ($v) => (string) $v, $documentoIds));

        try {
            $result = ($this->generarPdfConjunto)($id, $documentoIds, $archivoIds);

            return new Response($result['content'], Response::HTTP_OK, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $result['filename']),
            ]);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/avanzar-tramitacion', name: 'avanzar_tramitacion', methods: ['POST'])]
    public function avanzarTramitacion(string $id): JsonResponse
    {
        try {
            ($this->avanzarTramitacion)($id);

            return new JsonResponse(['message' => 'El expediente ha pasado a fase de tramitación.']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
