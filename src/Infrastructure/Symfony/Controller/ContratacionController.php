<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\DocumentoIntegridadService;
use App\Application\UseCase\ActualizarCondicionesPagoContratacionUseCase;
use App\Application\UseCase\DevolverPasoContratacionUseCase;
use App\Application\UseCase\ListarDocumentosContratacionUseCase;
use App\Application\UseCase\ListarHitosContratacionUseCase;
use App\Application\UseCase\ObtenerContratacionExpedienteUseCase;
use App\Application\UseCase\ValidarPasoContratacionUseCase;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/expedientes/{id}/contratacion', name: 'api_expedientes_contratacion_')]
final class ContratacionController extends AbstractController
{
    public function __construct(
        private ObtenerContratacionExpedienteUseCase $obtenerContratacion,
        private ValidarPasoContratacionUseCase $validarPaso,
        private DevolverPasoContratacionUseCase $devolverPaso,
        private ListarDocumentosContratacionUseCase $listarDocumentos,
        private ListarHitosContratacionUseCase $listarHitos,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteFirmaRepositoryInterface $firmaRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private DocumentoIntegridadService $integridadService,
        private ActualizarCondicionesPagoContratacionUseCase $actualizarCondicionesPago,
    ) {
    }

    #[Route(path: '', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerContratacion)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/condiciones-pago', name: 'condiciones_pago', methods: ['PUT'])]
    public function condicionesPago(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $result = ($this->actualizarCondicionesPago)(
                $id,
                (string) ($data['metodoPago'] ?? ''),
                (string) ($data['planPago'] ?? ''),
                (int) ($data['numCuotas'] ?? 1),
                isset($data['honorariosAcordados']) ? (float) $data['honorariosAcordados'] : null,
            );

            return new JsonResponse($result);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/documentos', name: 'documentos', methods: ['GET'])]
    public function documentos(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->listarDocumentos)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/hitos', name: 'hitos', methods: ['GET'])]
    public function hitos(string $id, Request $request): JsonResponse
    {
        $offset = max(0, (int) $request->query->get('offset', 0));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

        try {
            return new JsonResponse(($this->listarHitos)($id, $offset, $limit));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/documentos/{docId}/archivo', name: 'documento_archivo', methods: ['GET'])]
    public function documentoArchivo(string $id, string $docId): Response
    {
        $expedienteId = new ExpedienteId($id);
        $entregado = $this->documentoEntregadoRepository->findByExpedienteAndDocumento(
            $expedienteId,
            new TramiteDocumentoRequeridoId($docId),
        );

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

    #[Route(path: '/firmas/{tipo}/pdf', name: 'firma_pdf', methods: ['GET'])]
    public function firmaPdf(string $id, string $tipo): Response
    {
        $firma = $this->firmaRepository->findByExpedienteAndTipo(
            new ExpedienteId($id),
            TipoEscrito::fromString($tipo),
        );

        if (null === $firma || null === $firma->pdfFirmadoPath()) {
            return new JsonResponse(['message' => 'Documento firmado no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $content = $this->fileStorage->readRelativePath($firma->pdfFirmadoPath());
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        $headers = [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="documento-firmado.pdf"',
        ];

        $hash = $firma->pdfFirmadoSha256();
        if (null !== $hash) {
            $headers['X-Pdf-Sha256'] = $hash;
            $headers['X-Pdf-Integrity-Verified'] = $this->integridadService->verificar($content, $hash) ? 'true' : 'false';
        }

        return new Response($content, Response::HTTP_OK, $headers);
    }

    #[Route(path: '/validar/{paso}', name: 'validar', methods: ['POST'])]
    public function validar(string $id, string $paso): JsonResponse
    {
        try {
            ($this->validarPaso)($id, $paso);

            return new JsonResponse(($this->obtenerContratacion)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/devolver/{paso}', name: 'devolver', methods: ['POST'])]
    public function devolver(string $id, string $paso, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $nota = isset($data['nota']) ? (string) $data['nota'] : '';

        try {
            ($this->devolverPaso)($id, $paso, $nota);

            return new JsonResponse(($this->obtenerContratacion)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
