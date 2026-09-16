<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\Port\GastoFileStoragePort;
use App\Application\UseCase\ActualizarGastoUseCase;
use App\Application\UseCase\CrearGastoUseCase;
use App\Application\UseCase\EliminarFacturaGastoUseCase;
use App\Application\UseCase\EliminarGastoUseCase;
use App\Application\UseCase\ListarGastosUseCase;
use App\Application\UseCase\ObtenerGastoUseCase;
use App\Application\UseCase\SubirFacturaGastoUseCase;
use App\Domain\Repository\GastoRepositoryInterface;
use App\Domain\ValueObject\GastoId;
use App\Infrastructure\Http\UploadedArchivosExtractor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/api/gastos', name: 'api_gastos_')]
#[IsGranted('ROLE_USER')]
final class GastoController extends AbstractController
{
    public function __construct(
        private ListarGastosUseCase $listarGastos,
        private CrearGastoUseCase $crearGasto,
        private ObtenerGastoUseCase $obtenerGasto,
        private ActualizarGastoUseCase $actualizarGasto,
        private EliminarGastoUseCase $eliminarGasto,
        private SubirFacturaGastoUseCase $subirFactura,
        private EliminarFacturaGastoUseCase $eliminarFacturaUseCase,
        private GastoRepositoryInterface $gastoRepository,
        private GastoFileStoragePort $fileStorage,
        private UploadedArchivosExtractor $archivosExtractor,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $result = ($this->listarGastos)([
                'fechaDesde' => $request->query->getString('fechaDesde'),
                'fechaHasta' => $request->query->getString('fechaHasta'),
                'q' => $request->query->getString('q'),
                'categoria' => $request->query->getString('categoria'),
            ]);

            return new JsonResponse($result);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $gasto = ($this->crearGasto)(
                (string) ($data['concepto'] ?? ''),
                (string) ($data['importe'] ?? ''),
                (string) ($data['fecha'] ?? ''),
                isset($data['categoria']) ? (string) $data['categoria'] : null,
                isset($data['notas']) ? (string) $data['notas'] : null,
            );

            return new JsonResponse($gasto, Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerGasto)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $gasto = ($this->actualizarGasto)(
                $id,
                (string) ($data['concepto'] ?? ''),
                (string) ($data['importe'] ?? ''),
                (string) ($data['fecha'] ?? ''),
                isset($data['categoria']) ? (string) $data['categoria'] : null,
                isset($data['notas']) ? (string) $data['notas'] : null,
            );

            return new JsonResponse($gasto);
        } catch (\InvalidArgumentException $e) {
            $status = 'Gasto no encontrado.' === $e->getMessage()
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_BAD_REQUEST;

            return new JsonResponse(['message' => $e->getMessage()], $status);
        }
    }

    #[Route(path: '/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        try {
            ($this->eliminarGasto)($id);

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/{id}/factura', name: 'subir_factura', methods: ['POST'])]
    public function subirFactura(string $id, Request $request): JsonResponse
    {
        $archivos = $this->archivosExtractor->extract($request);
        if ([] === $archivos) {
            return new JsonResponse(['message' => 'Debe adjuntar al menos un archivo.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            return new JsonResponse(($this->subirFactura)($id, $archivos));
        } catch (\InvalidArgumentException $e) {
            $status = 'Gasto no encontrado.' === $e->getMessage()
                ? Response::HTTP_NOT_FOUND
                : Response::HTTP_BAD_REQUEST;

            return new JsonResponse(['message' => $e->getMessage()], $status);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{id}/factura', name: 'ver_factura', methods: ['GET'])]
    public function verFactura(string $id): Response
    {
        $gasto = $this->gastoRepository->findById(new GastoId($id));
        if (null === $gasto || !$gasto->tieneFactura() || null === $gasto->facturaPdfPath()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $absolute = $this->fileStorage->getAbsolutePath($gasto->facturaPdfPath());
        if (!is_file($absolute)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return new BinaryFileResponse($absolute, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="factura-gasto.pdf"',
        ]);
    }

    #[Route(path: '/{id}/factura', name: 'eliminar_factura', methods: ['DELETE'])]
    public function eliminarFactura(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->eliminarFacturaUseCase)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
