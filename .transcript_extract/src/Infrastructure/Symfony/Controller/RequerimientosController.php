<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\UseCase\AgregarDocumentoRequerimientoExpedienteUseCase;
use App\Application\UseCase\AvanzarFaseTramitacionUseCase;
use App\Application\UseCase\DevolverDocumentoRequerimientosUseCase;
use App\Application\UseCase\GuardarEscritoExpedienteUseCase;
use App\Application\UseCase\ObtenerRequerimientosExpedienteUseCase;
use App\Application\UseCase\ValidarDocumentoRequerimientosUseCase;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRequeridoRepositoryInterface;
use App\Domain\Repository\ExpedienteEscritoRepositoryInterface;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
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
        private AgregarDocumentoRequerimientoExpedienteUseCase $agregarDocumento,
        private ValidarDocumentoRequerimientosUseCase $validarDocumento,
        private DevolverDocumentoRequerimientosUseCase $devolverDocumento,
        private GuardarEscritoExpedienteUseCase $guardarEscrito,
        private AvanzarFaseTramitacionUseCase $avanzarTramitacion,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteDocumentoRequeridoRepositoryInterface $documentoRequeridoRepository,
        private ExpedienteEscritoRepositoryInterface $escritoRepository,
        private ExpedienteFileStoragePort $fileStorage,
    ) {
    }

    #[Route(path: '', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerRequerimientos)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/documentos', name: 'documentos_create', methods: ['POST'])]
    public function crearDocumento(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $result = ($this->agregarDocumento)(
                $id,
                (string) ($data['nombre'] ?? ''),
                (string) ($data['descripcion'] ?? ''),
                (bool) ($data['obligatorio'] ?? true),
                (string) ($data['tipo'] ?? 'individual'),
                (int) ($data['maxImagenes'] ?? 2),
            );

            return new JsonResponse($result, Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/documentos/{docId}/validar', name: 'documentos_validar', methods: ['POST'])]
    public function validarDocumento(string $id, string $docId): JsonResponse
    {
        try {
            ($this->validarDocumento)($id, $docId);

            return new JsonResponse(($this->obtenerRequerimientos)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/documentos/{docId}/devolver', name: 'documentos_devolver', methods: ['POST'])]
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

    #[Route(path: '/documentos/{docId}/archivo', name: 'documentos_archivo', methods: ['GET'])]
    public function documentoArchivo(string $id, string $docId): Response
    {
        $expedienteId = new ExpedienteId($id);
        $docReqId = new ExpedienteDocumentoRequeridoId($docId);

        $doc = $this->documentoRequeridoRepository->findById($docReqId);
        if (null === $doc || !$doc->expedienteId()->equals($expedienteId)) {
            return new JsonResponse(['message' => 'Documento no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $entrega = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento($expedienteId, $docReqId);
        if (null === $entrega || '' === $entrega->archivoPath()) {
            return new JsonResponse(['message' => 'Archivo no disponible.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $content = $this->fileStorage->readRelativePath($entrega->archivoPath());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="documento.pdf"',
        ]);
    }

    #[Route(path: '/escritos', name: 'escritos_create', methods: ['POST'])]
    public function crearEscrito(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $result = ($this->guardarEscrito)(
                $id,
                (string) ($data['titulo'] ?? ''),
                (string) ($data['contenidoHtml'] ?? ''),
            );

            return new JsonResponse($result, Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/escritos/{escritoId}/pdf', name: 'escritos_pdf', methods: ['GET'])]
    public function escritoPdf(string $id, string $escritoId): Response
    {
        $escrito = $this->escritoRepository->findById($escritoId);
        if (null === $escrito || !$escrito->expedienteId()->equals(new ExpedienteId($id))) {
            return new JsonResponse(['message' => 'Escrito no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $content = $this->fileStorage->readRelativePath($escrito->pdfPath());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="escrito.pdf"',
        ]);
    }

    #[Route(path: '/avanzar-tramitacion', name: 'avanzar_tramitacion', methods: ['POST'])]
    public function avanzarTramitacion(string $id): JsonResponse
    {
        try {
            ($this->avanzarTramitacion)($id);

            return new JsonResponse(['message' => 'Expediente avanzado a fase de tramitación.']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
