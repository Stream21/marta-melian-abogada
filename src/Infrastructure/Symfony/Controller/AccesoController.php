<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\ClienteFileStoragePort;
use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\Service\DocumentoIntegridadService;
use App\Application\UseCase\ActualizarClienteIdentidadAccesoUseCase;
use App\Application\UseCase\ReutilizarDocumentoIdentidadAccesoUseCase;
use App\Application\UseCase\CompletarPasoClienteUseCase;
use App\Application\UseCase\ExtraerDocumentoIdentidadUseCase;
use App\Application\UseCase\EnviarOtpFirmaAccesoUseCase;
use App\Application\UseCase\GenerarDocumentoFirmaAccesoUseCase;
use App\Application\UseCase\IniciarPagoAccesoUseCase;
use App\Application\UseCase\ListarDocumentosContratacionUseCase;
use App\Application\UseCase\ObtenerAccesoExpedienteUseCase;
use App\Application\UseCase\RegistrarFirmaDocumentoUseCase;
use App\Application\UseCase\SubirDocumentoContratacionUseCase;
use App\Application\UseCase\SubirDocumentoRequerimientosUseCase;
use App\Application\UseCase\VerificarOtpFirmaAccesoUseCase;
use App\Domain\Entity\TipoEscrito;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\DespachoConfigRepositoryInterface;
use App\Domain\Repository\ExpedienteDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteFirmaRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteDocumentoRequeridoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\TramiteDocumentoRequeridoId;
use App\Application\Port\DespachoFileStoragePort;
use App\Domain\Exception\ClienteDuplicadoExceptionInterface;
use App\Infrastructure\Http\ClienteDuplicadoJsonResponse;
use App\Application\Service\DocumentoEntregaArchivoResolver;
use App\Infrastructure\Http\SafeJsonResponse;
use App\Infrastructure\Http\Utf8Sanitizer;
use App\Infrastructure\Http\UploadedArchivosExtractor;
use App\Infrastructure\Http\UploadedFileMimeDetector;
use App\Infrastructure\Realtime\MercureJwtFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/acceso', name: 'api_acceso_')]
final class AccesoController extends AbstractController
{
    public function __construct(
        private ObtenerAccesoExpedienteUseCase $obtenerAcceso,
        private CompletarPasoClienteUseCase $completarPaso,
        private GenerarDocumentoFirmaAccesoUseCase $generarDocumentoFirma,
        private ExtraerDocumentoIdentidadUseCase $extraerDocumento,
        private ActualizarClienteIdentidadAccesoUseCase $actualizarIdentidad,
        private ReutilizarDocumentoIdentidadAccesoUseCase $reutilizarDocumentoIdentidad,
        private SubirDocumentoContratacionUseCase $subirDocumento,
        private SubirDocumentoRequerimientosUseCase $subirDocumentoRequerimientos,
        private RegistrarFirmaDocumentoUseCase $registrarFirma,
        private EnviarOtpFirmaAccesoUseCase $enviarOtpFirma,
        private VerificarOtpFirmaAccesoUseCase $verificarOtpFirma,
        private IniciarPagoAccesoUseCase $iniciarPago,
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ExpedienteDocumentoRepositoryInterface $documentoEntregadoRepository,
        private ExpedienteFirmaRepositoryInterface $firmaRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private DocumentoIntegridadService $integridadService,
        private MercureJwtFactory $jwtFactory,
        private string $mercurePublicUrl,
        private DespachoConfigRepositoryInterface $despachoRepository,
        private DespachoFileStoragePort $despachoStorage,
        private ClienteRepositoryInterface $clienteRepository,
        private ClienteFileStoragePort $clienteFileStorage,
        private UploadedFileMimeDetector $mimeDetector,
        private UploadedArchivosExtractor $archivosExtractor,
        private DocumentoEntregaArchivoResolver $archivoResolver,
    ) {
    }

    #[Route(path: '/{token}/mercure-token', name: 'mercure_token', methods: ['GET'])]
    public function mercureToken(string $token): JsonResponse
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            return new JsonResponse(['message' => 'Enlace no válido.'], Response::HTTP_NOT_FOUND);
        }

        if ('' === trim($this->mercurePublicUrl)) {
            return new JsonResponse(['message' => 'Mercure no configurado.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $topic = sprintf('/expedientes/%s/contratacion', $expediente->id()->value());
        $jwt = $this->jwtFactory->create(subscribeTopics: [$topic]);

        return new JsonResponse([
            'token' => $jwt,
            'hubUrl' => rtrim($this->mercurePublicUrl, '/') . '/.well-known/mercure',
            'topic' => $topic,
        ]);
    }

    #[Route(path: '/{token}/documentos/{docId}', name: 'subir_documento', methods: ['POST'])]
    public function subirDocumento(string $token, string $docId, Request $request): JsonResponse
    {
        $archivos = $this->archivosExtractor->extract($request);
        if ([] === $archivos) {
            return new JsonResponse(['message' => 'Debe adjuntar al menos un archivo.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            ($this->subirDocumento)($token, $docId, $archivos);

            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{token}/requerimientos/documentos/{docId}', name: 'subir_documento_requerimientos', methods: ['POST'])]
    public function subirDocumentoRequerimientos(string $token, string $docId, Request $request): JsonResponse
    {
        $archivos = $this->archivosExtractor->extract($request);
        if ([] === $archivos) {
            return new JsonResponse(['message' => 'Debe adjuntar al menos un archivo.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            ($this->subirDocumentoRequerimientos)($token, $docId, $archivos);

            return new SafeJsonResponse(($this->obtenerAcceso)($token));
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

    #[Route(path: '/{token}/requerimientos/documentos/{docId}/archivo', name: 'documento_requerimientos_archivo', methods: ['GET'])]
    public function documentoRequerimientosArchivo(string $token, string $docId, Request $request): Response
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            return new JsonResponse(['message' => 'Enlace no válido.'], Response::HTTP_NOT_FOUND);
        }

        $entregado = $this->documentoEntregadoRepository->findByExpedienteAndExpedienteDocumento(
            $expediente->id(),
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

    #[Route(path: '/{token}/documentos/{docId}/archivo', name: 'documento_archivo', methods: ['GET'])]
    public function documentoArchivo(string $token, string $docId): Response
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            return new JsonResponse(['message' => 'Enlace no válido.'], Response::HTTP_NOT_FOUND);
        }

        $entregado = $this->documentoEntregadoRepository->findByExpedienteAndDocumento(
            $expediente->id(),
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

    #[Route(path: '/{token}/firma/otp/enviar', name: 'firma_otp_enviar', methods: ['POST'])]
    public function enviarOtpFirma(string $token): JsonResponse
    {
        try {
            return new JsonResponse(($this->enviarOtpFirma)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    #[Route(path: '/{token}/firma/otp/verificar', name: 'firma_otp_verificar', methods: ['POST'])]
    public function verificarOtpFirma(string $token, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $codigo = (string) ($data['codigo'] ?? '');

        try {
            ($this->verificarOtpFirma)($token, $codigo);

            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{token}/firma/{tipo}', name: 'registrar_firma', methods: ['POST'])]
    public function registrarFirma(string $token, string $tipo, Request $request): JsonResponse
    {
        $file = $request->files->get('firma');
        if (!is_object($file) || !method_exists($file, 'getContent')) {
            return new JsonResponse(['message' => 'Debe adjuntar la firma.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            TipoEscrito::fromString($tipo);
            ($this->registrarFirma)(
                $token,
                $tipo,
                (string) $file->getContent(),
                $request->headers->get('User-Agent'),
                $request->getClientIp(),
            );

            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{token}/firma/{tipo}/firmado.pdf', name: 'firma_firmado_pdf', methods: ['GET'])]
    public function firmaFirmadoPdf(string $token, string $tipo): Response
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            return new JsonResponse(['message' => 'Enlace no válido.'], Response::HTTP_NOT_FOUND);
        }

        $firma = $this->firmaRepository->findByExpedienteAndTipo(
            $expediente->id(),
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

    #[Route(path: '/{token}/iniciar-pago', name: 'iniciar_pago', methods: ['POST'])]
    public function iniciarPago(string $token): JsonResponse
    {
        try {
            return new JsonResponse(($this->iniciarPago)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{token}/extraer-documento', name: 'extraer_documento', methods: ['POST'])]
    public function extraerDocumento(string $token, Request $request): JsonResponse
    {
        if (!$this->tokenValido($token)) {
            return new JsonResponse(['message' => 'Enlace de acceso no válido.'], Response::HTTP_NOT_FOUND);
        }

        try {
            [$tipo, $anverso, $reverso] = $this->parseDocumentoUpload($request);

            return new JsonResponse(($this->extraerDocumento)($tipo ?? '', $anverso ?? '', $reverso));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{token}/identidad/{lado}', name: 'identidad', methods: ['GET'], requirements: ['lado' => 'anverso|reverso'])]
    public function identidad(string $token, string $lado): Response
    {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            return new JsonResponse(['message' => 'Enlace no válido.'], Response::HTTP_NOT_FOUND);
        }

        $clienteId = $expediente->clienteId();
        if (null === $clienteId || '' === $clienteId) {
            return new JsonResponse(['message' => 'Cliente no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            return new JsonResponse(['message' => 'Cliente no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $relativePath = 'anverso' === $lado
            ? $cliente->documentoIdentidadAnversoPath()
            : $cliente->documentoIdentidadReversoPath();

        if (null === $relativePath || '' === $relativePath) {
            return new JsonResponse(['message' => 'Imagen no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $absolute = $this->clienteFileStorage->resolveAbsolutePath($relativePath);
        if (!is_readable($absolute)) {
            return new JsonResponse(['message' => 'Imagen no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($absolute, 200, [
            'Content-Type' => $this->mimeDetector->detectFromPath($absolute),
            'Content-Disposition' => 'inline; filename="documento-identidad-' . $lado . '.jpg"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    #[Route(path: '/{token}/reutilizar-documento-identidad', name: 'reutilizar_documento_identidad', methods: ['POST'])]
    public function reutilizarDocumentoIdentidad(string $token): JsonResponse
    {
        try {
            ($this->reutilizarDocumentoIdentidad)($token);

            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{token}/datos-identidad', name: 'datos_identidad', methods: ['POST'])]
    public function datosIdentidad(string $token, Request $request): JsonResponse
    {
        try {
            [$tipo, $anverso, $reverso, $soloDatos] = $this->parseDocumentoUpload($request, true);
            $datos = $request->request->all('datos');
            if (!is_array($datos)) {
                $datos = [];
            }

            ($this->actualizarIdentidad)(
                $token,
                $tipo,
                $anverso,
                $reverso,
                $this->inputFromArray($datos),
                $soloDatos,
                filter_var($request->request->get('permitirDuplicado', false), FILTER_VALIDATE_BOOLEAN),
            );

            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (ClienteDuplicadoExceptionInterface $e) {
            return ClienteDuplicadoJsonResponse::create($e);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{token}/despacho/logo', name: 'despacho_logo', methods: ['GET'])]
    public function despachoLogo(string $token): Response
    {
        if (!$this->tokenValido($token)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $config = $this->despachoRepository->find();
        $path = $config?->logoPath();
        if (null === $path || '' === $path) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $fullPath = $this->despachoStorage->getAbsolutePath($path);
        if (!is_file($fullPath)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($fullPath, 200, [
            'Content-Type' => $this->mimeDetector->detectFromPath($fullPath),
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    #[Route(path: '/{token}', name: 'show', methods: ['GET'])]
    public function show(string $token): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/{token}/completar-paso', name: 'completar_paso', methods: ['POST'])]
    public function completarPaso(string $token, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $paso = (string) ($data['paso'] ?? '');

        try {
            ($this->completarPaso)($token, $paso);

            return new JsonResponse(($this->obtenerAcceso)($token));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{token}/firma/{tipo}/pdf', name: 'firma_pdf', methods: ['GET'])]
    public function firmaPdf(string $token, string $tipo): Response
    {
        try {
            $pdf = ($this->generarDocumentoFirma)($token, $tipo);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="documento-' . $tipo . '.pdf"',
        ]);
    }

    private function tokenValido(string $token): bool
    {
        return null !== $this->expedienteRepository->findByAccessToken($token);
    }

    /**
     * @return array{0: string|null, 1: string|null, 2: string|null, 3: bool}
     */
    private function parseDocumentoUpload(Request $request, bool $archivosOpcionales = false): array
    {
        $soloDatos = '1' === (string) $request->request->get('soloDatos', '');
        $tipo = $request->request->has('tipoEscaneo')
            ? (string) $request->request->get('tipoEscaneo')
            : null;
        $anversoFile = $request->files->get('anverso');
        $reversoFile = $request->files->get('reverso');

        $anverso = null;
        if (is_object($anversoFile) && method_exists($anversoFile, 'getContent')) {
            $anverso = (string) $anversoFile->getContent();
        }

        $reverso = null;
        if (is_object($reversoFile) && method_exists($reversoFile, 'getContent')) {
            $reverso = (string) $reversoFile->getContent();
        }

        if (!$archivosOpcionales && !$soloDatos && (null === $anverso || '' === $anverso)) {
            throw new \InvalidArgumentException('Debe adjuntar la imagen del anverso del documento.');
        }

        return [$tipo, $anverso, $reverso, $soloDatos];
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
