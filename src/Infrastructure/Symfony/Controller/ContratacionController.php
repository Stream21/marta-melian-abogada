<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\DocumentoIntegridadService;
use App\Application\UseCase\ActualizarCondicionesPagoContratacionUseCase;
use App\Application\UseCase\ActualizarDatosClienteContratacionUseCase;
use App\Application\UseCase\ActualizarDocumentoIdentidadContratacionUseCase;
use App\Application\UseCase\DevolverPasoContratacionUseCase;
use App\Application\UseCase\ListarDocumentosContratacionUseCase;
use App\Application\UseCase\ObtenerContratacionExpedienteUseCase;
use App\Application\UseCase\ValidarPasoContratacionUseCase;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Exception\ClienteDuplicadoExceptionInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use App\Infrastructure\Http\ClienteDuplicadoJsonResponse;
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
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteFirmaRepositoryInterface $firmaRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private DocumentoIntegridadService $integridadService,
        private ActualizarCondicionesPagoContratacionUseCase $actualizarCondicionesPago,
        private ActualizarDocumentoIdentidadContratacionUseCase $actualizarDocumentoIdentidad,
        private ActualizarDatosClienteContratacionUseCase $actualizarDatosCliente,
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

    #[Route(path: '/documento-identidad', name: 'documento_identidad', methods: ['POST'])]
    public function documentoIdentidad(string $id, Request $request): JsonResponse
    {
        try {
            [$tipo, $anverso, $reverso] = $this->parseDocumentoUpload($request);
            $datos = $request->request->all('datos');
            if (!is_array($datos)) {
                $datos = [];
            }
            $permitirDuplicado = filter_var($request->request->get('permitirDuplicado', false), FILTER_VALIDATE_BOOLEAN);

            ($this->actualizarDocumentoIdentidad)(
                $id,
                $tipo,
                $anverso,
                $reverso,
                $this->inputFromArray($datos),
                $permitirDuplicado,
            );

            return new JsonResponse(($this->obtenerContratacion)($id));
        } catch (ClienteDuplicadoExceptionInterface $e) {
            return ClienteDuplicadoJsonResponse::create($e);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/datos-cliente', name: 'datos_cliente', methods: ['PUT'])]
    public function datosCliente(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        if (!is_array($data)) {
            $data = [];
        }

        try {
            $permitirDuplicado = (bool) ($data['permitirDuplicado'] ?? false);
            unset($data['permitirDuplicado']);

            ($this->actualizarDatosCliente)($id, $this->inputFromArray($data), $permitirDuplicado);

            return new JsonResponse(($this->obtenerContratacion)($id));
        } catch (ClienteDuplicadoExceptionInterface $e) {
            return ClienteDuplicadoJsonResponse::create($e);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
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
        $motivos = isset($data['motivos']) && is_array($data['motivos'])
            ? array_values(array_filter(array_map('strval', $data['motivos'])))
            : [];

        try {
            ($this->devolverPaso)($id, $paso, $nota, $motivos);

            return new JsonResponse(($this->obtenerContratacion)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @return array{0: string, 1: string, 2: string|null}
     */
    private function parseDocumentoUpload(Request $request): array
    {
        $tipo = (string) ($request->request->get('tipoEscaneo') ?? '');
        $anversoFile = $request->files->get('anverso');
        $reversoFile = $request->files->get('reverso');

        if (!is_object($anversoFile) || !method_exists($anversoFile, 'getContent')) {
            throw new \InvalidArgumentException('Debe adjuntar la imagen del anverso del documento.');
        }

        $anverso = (string) $anversoFile->getContent();
        $reverso = null;
        if (is_object($reversoFile) && method_exists($reversoFile, 'getContent')) {
            $reverso = (string) $reversoFile->getContent();
        }

        return [$tipo, $anverso, $reverso];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function inputFromArray(array $data): \App\Application\DTO\ClienteInput
    {
        return new \App\Application\DTO\ClienteInput(
            (string) ($data['nombre'] ?? ''),
            (string) ($data['nacionalidad'] ?? ''),
            (string) ($data['tipoDocumento'] ?? ''),
            (string) ($data['numDocumento'] ?? ''),
            isset($data['fechaNacimiento']) ? (string) $data['fechaNacimiento'] : null,
            (string) ($data['lugarNacimiento'] ?? ''),
            (string) ($data['estadoCivil'] ?? ''),
            (string) ($data['domicilio'] ?? ''),
            (string) ($data['codigoPostal'] ?? ''),
            (string) ($data['ciudad'] ?? ''),
            (string) ($data['provincia'] ?? ''),
            (string) ($data['nombrePadre'] ?? ''),
            (string) ($data['nombreMadre'] ?? ''),
            (string) ($data['telefono'] ?? ''),
            (string) ($data['email'] ?? ''),
        );
    }
}
