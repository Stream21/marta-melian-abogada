<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\ExpedienteFileStoragePort;
use App\Application\UseCase\ActualizarDocumentoRequerimientoMercurioUseCase;
use App\Application\UseCase\AgregarCamposARequerimientoMercurioUseCase;
use App\Application\UseCase\AgregarDocumentoARequerimientoMercurioUseCase;
use App\Application\UseCase\AgregarRequerimientoMercurioUseCase;
use App\Application\UseCase\AvanzarResolucionUseCase;
use App\Application\UseCase\EliminarDocumentoRequerimientoMercurioUseCase;
use App\Application\UseCase\GestionarCamposRequerimientoMercurioUseCase;
use App\Application\UseCase\GuardarCamposRequerimientoMercurioUseCase;
use App\Application\UseCase\ObtenerTramitacionExpedienteUseCase;
use App\Application\UseCase\PresentarRequerimientoMercurioUseCase;
use App\Application\UseCase\RegistrarPresentacionTelematicaUseCase;
use App\Application\UseCase\RegistrarSeguimientoExtranjeriaUseCase;
use App\Application\UseCase\SubirArchivoDocumentoRequerimientoMercurioUseCase;
use App\Application\UseCase\SubirArchivoRequerimientoMercurioUseCase;
use App\Application\UseCase\ValidarDocumentoRequerimientoMercurioUseCase;
use App\Application\UseCase\VincularEscritoRequerimientoMercurioUseCase;
use App\Domain\Repository\ExpedientePresentacionTelematicaRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoDocumentoRepositoryInterface;
use App\Domain\Repository\ExpedienteRequerimientoMercurioRepositoryInterface;
use App\Domain\ValueObject\ExpedienteRequerimientoDocumentoId;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\ExpedienteRequerimientoMercurioId;
use App\Infrastructure\Http\UploadedFileMimeDetector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/expedientes/{id}/tramitacion', name: 'api_expedientes_tramitacion_')]
#[IsGranted('ROLE_USER')]
final class TramitacionController extends AbstractController
{
    public function __construct(
        private ObtenerTramitacionExpedienteUseCase $obtener,
        private RegistrarPresentacionTelematicaUseCase $registrarPresentacion,
        private RegistrarSeguimientoExtranjeriaUseCase $registrarSeguimiento,
        private AgregarRequerimientoMercurioUseCase $agregarRequerimiento,
        private AgregarDocumentoARequerimientoMercurioUseCase $agregarDocumentoRequerimiento,
        private ActualizarDocumentoRequerimientoMercurioUseCase $actualizarDocumentoRequerimiento,
        private EliminarDocumentoRequerimientoMercurioUseCase $eliminarDocumentoRequerimiento,
        private AgregarCamposARequerimientoMercurioUseCase $agregarCamposRequerimiento,
        private GestionarCamposRequerimientoMercurioUseCase $gestionarCamposRequerimiento,
        private SubirArchivoRequerimientoMercurioUseCase $subirArchivo,
        private SubirArchivoDocumentoRequerimientoMercurioUseCase $subirArchivoDocumento,
        private ValidarDocumentoRequerimientoMercurioUseCase $validarDocumento,
        private GuardarCamposRequerimientoMercurioUseCase $guardarCampos,
        private PresentarRequerimientoMercurioUseCase $presentarRequerimiento,
        private VincularEscritoRequerimientoMercurioUseCase $vincularEscrito,
        private AvanzarResolucionUseCase $avanzarResolucion,
        private ExpedientePresentacionTelematicaRepositoryInterface $presentacionRepository,
        private ExpedienteRequerimientoMercurioRepositoryInterface $requerimientoRepository,
        private ExpedienteRequerimientoDocumentoRepositoryInterface $requerimientoDocumentoRepository,
        private ExpedienteFileStoragePort $fileStorage,
        private UploadedFileMimeDetector $mimeDetector,
    ) {
    }

    #[Route(path: '', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/presentacion', name: 'presentacion', methods: ['POST'])]
    public function presentacion(string $id, Request $request): JsonResponse
    {
        try {
            $presentacion = $this->requireUploadedFile($request, 'presentacion');
            $justificante = $this->requireUploadedFile($request, 'justificante');

            ($this->registrarPresentacion)(
                $id,
                $presentacion,
                $justificante,
                (string) $request->request->get('fechaPresentacion', ''),
            );

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/seguimiento', name: 'seguimiento', methods: ['PATCH', 'POST'])]
    public function seguimiento(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = $request->request->all();
        }

        try {
            ($this->registrarSeguimiento)(
                $id,
                (string) ($data['numeroExpedienteExtranjeria'] ?? ''),
            );

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos', name: 'requerimientos_crear', methods: ['POST'])]
    public function crearRequerimiento(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $documentos = is_array($data['documentos'] ?? null) ? $data['documentos'] : [];
            $campos = is_array($data['campos'] ?? null) ? $data['campos'] : [];
            $plantillaFrom = isset($data['plantillaFrom']) ? (string) $data['plantillaFrom'] : null;
            if ('' === $plantillaFrom) {
                $plantillaFrom = null;
            }

            $reqId = ($this->agregarRequerimiento)(
                $id,
                (string) ($data['tipo'] ?? 'documento'),
                (string) ($data['destino'] ?? 'despacho'),
                (string) ($data['nombre'] ?? ''),
                (string) ($data['descripcion'] ?? ''),
                $documentos,
                $campos,
                $plantillaFrom,
            );

            return new JsonResponse(['id' => $reqId, ...($this->obtener)($id)], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/documentos', name: 'requerimiento_documento_crear', methods: ['POST'])]
    public function agregarDocumentoRequerimiento(string $id, string $reqId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            ($this->agregarDocumentoRequerimiento)(
                $id,
                $reqId,
                (string) ($data['nombre'] ?? ''),
                (string) ($data['responsable'] ?? 'abogado'),
                (string) ($data['descripcion'] ?? ''),
                (bool) ($data['obligatorio'] ?? true),
                (int) ($data['numeroArchivos'] ?? 1),
            );

            return new JsonResponse(($this->obtener)($id), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/documentos/{docId}', name: 'requerimiento_documento_actualizar', methods: ['PATCH'])]
    public function actualizarDocumentoRequerimiento(string $id, string $reqId, string $docId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $nombre = array_key_exists('nombre', $data) ? (string) $data['nombre'] : null;
            $responsable = array_key_exists('responsable', $data) ? (string) $data['responsable'] : null;

            ($this->actualizarDocumentoRequerimiento)($id, $reqId, $docId, $nombre, $responsable);

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/documentos/{docId}', name: 'requerimiento_documento_eliminar', methods: ['DELETE'])]
    public function eliminarDocumentoRequerimiento(string $id, string $reqId, string $docId): JsonResponse
    {
        try {
            ($this->eliminarDocumentoRequerimiento)($id, $reqId, $docId);

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/campos/definir', name: 'requerimiento_campos_definir', methods: ['POST'])]
    public function agregarCamposRequerimiento(string $id, string $reqId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $campos = is_array($data['campos'] ?? null) ? $data['campos'] : [];

        try {
            ($this->agregarCamposRequerimiento)($id, $reqId, $campos);

            return new JsonResponse(($this->obtener)($id), Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/campos', name: 'requerimiento_campos_gestionar', methods: ['PUT'])]
    public function gestionarCamposRequerimiento(string $id, string $reqId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $campos = is_array($data['campos'] ?? null) ? $data['campos'] : [];
        $actualizarMeta = array_key_exists('formularioNombre', $data)
            || array_key_exists('formularioCometido', $data);

        try {
            ($this->gestionarCamposRequerimiento)(
                $id,
                $reqId,
                $campos,
                isset($data['formularioNombre']) ? (string) $data['formularioNombre'] : null,
                isset($data['formularioCometido']) ? (string) $data['formularioCometido'] : null,
                $actualizarMeta,
            );

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/archivo', name: 'requerimiento_archivo', methods: ['POST'])]
    public function subirArchivoRequerimiento(string $id, string $reqId, Request $request): JsonResponse
    {
        try {
            $file = $this->requireUploadedFile($request, 'archivo');
            ($this->subirArchivo)($id, $reqId, $file, false);

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/presentar', name: 'requerimiento_presentar', methods: ['POST'])]
    public function presentarRequerimiento(string $id, string $reqId, Request $request): JsonResponse
    {
        try {
            $justificante = $this->requireUploadedFile($request, 'justificante');
            $presentacion = null;
            if ($request->files->has('presentacion') && null !== $request->files->get('presentacion')) {
                $presentacion = $this->requireUploadedFile($request, 'presentacion');
            }
            $archivo = null;
            if ($request->files->has('archivo') && null !== $request->files->get('archivo')) {
                $archivo = $this->requireUploadedFile($request, 'archivo');
            }
            ($this->presentarRequerimiento)($id, $reqId, $justificante, $presentacion, $archivo);

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/documentos/{docId}/archivo', name: 'requerimiento_documento_archivo', methods: ['POST'])]
    public function subirArchivoDocumentoRequerimiento(string $id, string $reqId, string $docId, Request $request): JsonResponse
    {
        try {
            $file = $this->requireUploadedFile($request, 'archivo');
            ($this->subirArchivoDocumento)($id, $reqId, $docId, $file, false);

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/documentos/{docId}/validar', name: 'requerimiento_documento_validar', methods: ['POST'])]
    public function validarDocumentoRequerimiento(string $id, string $reqId, string $docId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            ($this->validarDocumento)(
                $id,
                $reqId,
                $docId,
                (string) ($data['accion'] ?? 'validar'),
                (string) ($data['notaRechazo'] ?? ''),
            );

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/campos', name: 'requerimiento_campos', methods: ['PATCH', 'POST'])]
    public function guardarCamposRequerimiento(string $id, string $reqId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $valores = is_array($data['valores'] ?? null) ? $data['valores'] : (is_array($data['campos'] ?? null) ? $data['campos'] : []);

        try {
            ($this->guardarCampos)($id, $reqId, $valores, false);

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/requerimientos/{reqId}/documentos/{docId}/archivo-descarga', name: 'requerimiento_documento_archivo_get', methods: ['GET'])]
    public function descargarArchivoDocumentoRequerimiento(string $id, string $reqId, string $docId): Response
    {
        $doc = $this->requerimientoDocumentoRepository->findById(new ExpedienteRequerimientoDocumentoId($docId));
        if (null === $doc || $doc->requerimientoId()->value() !== $reqId || null === $doc->archivoPath()) {
            return new JsonResponse(['message' => 'Archivo no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        return $this->fileResponse($doc->archivoPath(), $doc->nombre() . '.pdf');
    }

    #[Route(path: '/requerimientos/{reqId}/escrito', name: 'requerimiento_escrito', methods: ['POST'])]
    public function vincularEscrito(string $id, string $reqId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            ($this->vincularEscrito)($id, $reqId, (string) ($data['escritoId'] ?? ''));

            return new JsonResponse(($this->obtener)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/avanzar-resolucion', name: 'avanzar_resolucion', methods: ['POST'])]
    public function avanzarResolucion(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            ($this->avanzarResolucion)(
                $id,
                isset($data['fechaVencimientoFase']) ? (string) $data['fechaVencimientoFase'] : null,
            );

            return new JsonResponse(['message' => 'Expediente pasado a resolución.']);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/presentacion/archivo/{tipo}', name: 'presentacion_archivo', methods: ['GET'], requirements: ['tipo' => 'presentacion|justificante'])]
    public function descargarPresentacion(string $id, string $tipo): Response
    {
        $presentacion = $this->presentacionRepository->findByExpediente(new ExpedienteId($id));
        if (null === $presentacion) {
            return new JsonResponse(['message' => 'Presentación no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $path = 'presentacion' === $tipo
            ? $presentacion->presentacionPath()
            : $presentacion->justificantePath();

        return $this->fileResponse($path, $tipo . '.pdf');
    }

    #[Route(path: '/requerimientos/{reqId}/archivo-descarga', name: 'requerimiento_archivo_get', methods: ['GET'])]
    public function descargarArchivoRequerimiento(string $id, string $reqId): Response
    {
        $req = $this->requerimientoRepository->findById(new ExpedienteRequerimientoMercurioId($reqId));
        if (null === $req || $req->expedienteId()->value() !== $id || null === $req->archivoPath()) {
            return new JsonResponse(['message' => 'Archivo no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        return $this->fileResponse($req->archivoPath(), $req->archivoNombre() ?? 'documento.pdf');
    }

    #[Route(path: '/requerimientos/{reqId}/justificante', name: 'requerimiento_justificante_get', methods: ['GET'])]
    public function descargarJustificanteRequerimiento(string $id, string $reqId): Response
    {
        $req = $this->requerimientoRepository->findById(new ExpedienteRequerimientoMercurioId($reqId));
        if (null === $req || $req->expedienteId()->value() !== $id || null === $req->justificantePresentacionPath()) {
            return new JsonResponse(['message' => 'Justificante no encontrado.'], Response::HTTP_NOT_FOUND);
        }

        return $this->fileResponse($req->justificantePresentacionPath(), 'justificante.pdf');
    }

    /**
     * @return array{content: string, filename: string}
     */
    private function requireUploadedFile(Request $request, string $key): array
    {
        $file = $request->files->get($key);
        if (!is_object($file) || !method_exists($file, 'getContent')) {
            throw new \InvalidArgumentException(sprintf('Falta el archivo «%s».', $key));
        }

        $content = (string) $file->getContent();
        if ('' === $content) {
            throw new \InvalidArgumentException(sprintf('El archivo «%s» está vacío.', $key));
        }

        $filename = method_exists($file, 'getClientOriginalName')
            ? (string) $file->getClientOriginalName()
            : $key . '.pdf';

        return ['content' => $content, 'filename' => $filename];
    }

    private function fileResponse(string $relativePath, string $filename): Response
    {
        try {
            $content = $this->fileStorage->readRelativePath($relativePath);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        $absolute = $this->fileStorage->getAbsolutePath($relativePath);

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => $this->mimeDetector->detectFromPath($absolute),
            'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
        ]);
    }
}
