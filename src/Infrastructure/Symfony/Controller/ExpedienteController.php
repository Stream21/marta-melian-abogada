<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\AltaExpedienteInput;
use App\Application\DTO\CrearExpedienteInput;
use App\Application\DTO\ExpedienteResponseMapper;
use App\Application\Service\ExpedienteAvisosAggregator;
use App\Application\UseCase\AltaExpedienteUseCase;
use App\Application\UseCase\CrearExpedienteUseCase;
use App\Application\UseCase\ListarAuditoriaExpedienteUseCase;
use App\Application\UseCase\ListarExpedientesUseCase;
use App\Application\UseCase\ObtenerExpedienteUseCase;
use App\Application\UseCase\ObtenerFacturacionExpedienteUseCase;
use App\Application\UseCase\SincronizarCobrosExpedienteHoldedUseCase;
use App\Application\UseCase\VincularExpedienteClienteUseCase;
use App\Domain\Exception\ClienteDuplicadoExceptionInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Infrastructure\Http\ClienteDuplicadoJsonResponse;
use App\Domain\ValueObject\ExpedienteId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/expedientes', name: 'api_expedientes_')]
final class ExpedienteController extends AbstractController
{
    public function __construct(
        private ListarExpedientesUseCase $listarExpedientes,
        private CrearExpedienteUseCase $crearExpediente,
        private AltaExpedienteUseCase $altaExpediente,
        private ObtenerExpedienteUseCase $obtenerExpediente,
        private ListarAuditoriaExpedienteUseCase $listarAuditoria,
        private PaymentRepositoryInterface $paymentRepository,
        private VincularExpedienteClienteUseCase $vincularExpediente,
        private ObtenerFacturacionExpedienteUseCase $obtenerFacturacion,
        private SincronizarCobrosExpedienteHoldedUseCase $sincronizarCobrosHolded,
        private ExpedienteAvisosAggregator $avisosAggregator,
        private string $frontendBaseUrl = 'http://localhost:5173',
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $expedientes = ($this->listarExpedientes)();
        $avisosPorExpediente = $this->avisosAggregator->aggregate($expedientes);

        return new JsonResponse(array_map(
            fn ($e) => ExpedienteResponseMapper::fromDomain(
                $e,
                $this->frontendBaseUrl,
                $avisosPorExpediente[$e->id()->value()] ?? null,
            ),
            $expedientes,
        ));
    }

    #[Route(path: '', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $input = new CrearExpedienteInput(
            numero: $data['numero'] ?? '',
            titulo: $data['titulo'] ?? '',
            estado: $data['estado'] ?? 'abierto',
            clientName: $data['clientName'] ?? '',
            caseReference: $data['caseReference'] ?? '',
            folderPath: $data['folderPath'] ?? '',
        );

        $expediente = ($this->crearExpediente)($input);

        return new JsonResponse(
            ExpedienteResponseMapper::fromDomain($expediente, $this->frontendBaseUrl),
            JsonResponse::HTTP_CREATED,
        );
    }

    #[Route(path: '/alta', name: 'alta', methods: ['POST'])]
    public function alta(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $result = ($this->altaExpediente)(new AltaExpedienteInput(
                clienteId: isset($data['clienteId']) ? (string) $data['clienteId'] : null,
                telefono: isset($data['telefono']) ? (string) $data['telefono'] : null,
                email: isset($data['email']) ? (string) $data['email'] : null,
                tramiteId: (string) ($data['tramiteId'] ?? ''),
                honorariosAcordados: (float) ($data['honorariosAcordados'] ?? 0),
                metodoPago: (string) ($data['metodoPago'] ?? 'manual'),
                planPago: (string) ($data['planPago'] ?? 'unico'),
                numCuotas: (int) ($data['numCuotas'] ?? 1),
                notificar: (bool) ($data['notificar'] ?? true),
                canalesNotificacion: array_values(array_filter(
                    (array) ($data['canalesNotificacion'] ?? []),
                    fn ($c) => is_string($c) && in_array($c, ['whatsapp', 'email'], true),
                )),
                fechaVencimientoFase: isset($data['fechaVencimientoFase']) ? (string) $data['fechaVencimientoFase'] : null,
                permitirDuplicado: (bool) ($data['permitirDuplicado'] ?? false),
            ));
        } catch (ClienteDuplicadoExceptionInterface $e) {
            return ClienteDuplicadoJsonResponse::create($e);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'expediente' => $result->expediente,
            'accessUrl' => $result->accessUrl,
            'canalesNotificados' => $result->canalesNotificados,
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route(path: '/{id}', name: 'show', methods: ['GET'], priority: -1)]
    public function show(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerExpediente)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/{id}/auditoria', name: 'auditoria', methods: ['GET'])]
    public function auditoria(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->listarAuditoria)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/{id}/payments', name: 'payments', methods: ['GET'])]
    public function payments(string $id): JsonResponse
    {
        $expedienteId = new ExpedienteId($id);
        $payments = $this->paymentRepository->findByExpediente($expedienteId);
        $list = [];
        foreach ($payments as $p) {
            $list[] = [
                'id' => $p->id()->value(),
                'expedienteId' => $p->expedienteId()->value(),
                'status' => $p->status()->value,
                'type' => $p->type()->value,
                'amount' => $p->amount(),
                'holdedEstado' => $p->holdedEstado()->value,
                'holdedEstadoLabel' => $p->holdedEstado()->label(),
                'holdedSyncError' => $p->holdedSyncError(),
                'holdedInvoiceId' => $p->holdedInvoiceId(),
                'pdfUrl' => $p->invoicePdfUrl(),
                'cuotaNumero' => $p->cuotaNumero(),
                'createdAt' => $p->createdAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return new JsonResponse($list);
    }

    #[Route(path: '/{id}/facturacion', name: 'facturacion', methods: ['GET'])]
    public function facturacion(string $id): JsonResponse
    {
        try {
            return new JsonResponse(($this->obtenerFacturacion)($id));
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route(path: '/{id}/sincronizar-holded', name: 'sincronizar_holded', methods: ['POST'])]
    public function sincronizarHolded(string $id): JsonResponse
    {
        try {
            $result = ($this->sincronizarCobrosHolded)($id);

            if (!$result['success']) {
                return new JsonResponse($result, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return new JsonResponse($result);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route(path: '/{id}/vincular', name: 'vincular', methods: ['PUT'])]
    public function vincular(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            ($this->vincularExpediente)(
                $id,
                isset($data['clienteId']) ? (string) $data['clienteId'] : null,
                isset($data['tramiteId']) ? (string) $data['tramiteId'] : null,
            );
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['success' => true]);
    }
}
