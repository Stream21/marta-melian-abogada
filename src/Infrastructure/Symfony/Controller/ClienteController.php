<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\ClienteInput;
use App\Application\DTO\ClienteResponseMapper;
use App\Application\Port\ClienteFileStoragePort;
use App\Application\UseCase\BuscarClientesUseCase;
use App\Application\UseCase\CrearClienteConDocumentoUseCase;
use App\Application\UseCase\ExtraerDocumentoIdentidadUseCase;
use App\Application\UseCase\GuardarClienteUseCase;
use App\Application\UseCase\ListarClientesUseCase;
use App\Application\UseCase\ObtenerClienteDetalleUseCase;
use App\Application\UseCase\SincronizarClienteHoldedUseCase;
use App\Domain\Exception\TelefonoClienteDuplicadoException;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Infrastructure\Http\UploadedFileMimeDetector;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/clientes', name: 'api_clientes_')]
final class ClienteController extends AbstractController
{
    public function __construct(
        private ListarClientesUseCase $listar,
        private ObtenerClienteDetalleUseCase $obtenerDetalle,
        private GuardarClienteUseCase $guardar,
        private CrearClienteConDocumentoUseCase $crearConDocumento,
        private ExtraerDocumentoIdentidadUseCase $extraerDocumento,
        private BuscarClientesUseCase $buscarClientes,
        private SincronizarClienteHoldedUseCase $sincronizarHolded,
        private ClienteRepositoryInterface $clienteRepository,
        private ClienteFileStoragePort $fileStorage,
        private UploadedFileMimeDetector $mimeDetector,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return new JsonResponse(($this->listar)());
    }

    #[Route(path: '/buscar', name: 'buscar', methods: ['GET'])]
    public function buscar(Request $request): JsonResponse
    {
        $query = (string) ($request->query->get('q') ?? $request->query->get('telefono', ''));

        return new JsonResponse(($this->buscarClientes)($query));
    }

    #[Route(path: '/extraer-documento', name: 'extraer_documento', methods: ['POST'])]
    public function extraerDocumento(Request $request): JsonResponse
    {
        try {
            [$tipo, $anverso, $reverso] = $this->parseDocumentoUpload($request);

            return new JsonResponse(($this->extraerDocumento)($tipo, $anverso, $reverso));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '[0-9a-f-]{36}'])]
    public function show(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerDetalle)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/{id}/documento-identidad/{lado}', name: 'documento_identidad', methods: ['GET'], requirements: ['id' => '[0-9a-f-]{36}', 'lado' => 'anverso|reverso'])]
    public function documentoIdentidad(string $id, string $lado): Response
    {
        $cliente = $this->clienteRepository->findById(new ClienteId($id));
        if (null === $cliente) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $relative = 'anverso' === $lado
            ? $cliente->documentoIdentidadAnversoPath()
            : $cliente->documentoIdentidadReversoPath();

        if (null === $relative || '' === $relative) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $absolute = $this->fileStorage->resolveAbsolutePath($relative);
        if (!is_file($absolute)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($absolute);
        $response->headers->set('Content-Type', $this->mimeDetector->detectFromPath($absolute));

        return $response;
    }

    #[Route(path: '/{id}/sincronizar-holded', name: 'sincronizar_holded', methods: ['POST'], requirements: ['id' => '[0-9a-f-]{36}'])]
    public function sincronizarHolded(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $forzar = (bool) ($data['forzar'] ?? true);

        try {
            $result = ($this->sincronizarHolded)($id, $forzar);

            return new JsonResponse($result, $result['success'] ? Response::HTTP_OK : Response::HTTP_BAD_GATEWAY);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            [$tipo, $anverso, $reverso] = $this->parseDocumentoUpload($request);
            $datos = $request->request->all('datos');
            if (!is_array($datos)) {
                $datos = [];
            }

            $cliente = ($this->crearConDocumento)(
                $tipo,
                $anverso,
                $reverso,
                $this->inputFromArray($datos),
            );
        } catch (TelefonoClienteDuplicadoException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'clienteExistenteId' => $e->clienteExistenteId,
                'clienteExistenteNombre' => $e->clienteExistenteNombre,
            ], Response::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(ClienteResponseMapper::fromDomain($cliente), Response::HTTP_CREATED);
    }

    #[Route(path: '/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '[0-9a-f-]{36}'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $cliente = ($this->guardar)($id, $this->inputFromArray($data));
        } catch (TelefonoClienteDuplicadoException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'clienteExistenteId' => $e->clienteExistenteId,
                'clienteExistenteNombre' => $e->clienteExistenteNombre,
            ], Response::HTTP_CONFLICT);
        } catch (\InvalidArgumentException $e) {
            $status = str_contains($e->getMessage(), 'no se pueden modificar') || str_contains($e->getMessage(), 'no se puede modificar')
                ? Response::HTTP_CONFLICT
                : Response::HTTP_BAD_REQUEST;

            return new JsonResponse(['message' => $e->getMessage()], $status);
        }

        return new JsonResponse(ClienteResponseMapper::fromDomain($cliente));
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
    private function inputFromArray(array $data): ClienteInput
    {
        return new ClienteInput(
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
