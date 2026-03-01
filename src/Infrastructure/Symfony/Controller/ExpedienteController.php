<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\CrearExpedienteInput;
use App\Application\DTO\ExpedienteResponse;
use App\Application\UseCase\CrearExpedienteUseCase;
use App\Application\UseCase\ListarExpedientesUseCase;
use App\Domain\Entity\Expediente;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/expedientes', name: 'api_expedientes_')]
final class ExpedienteController extends AbstractController
{
    public function __construct(
        private ListarExpedientesUseCase $listarExpedientes,
        private CrearExpedienteUseCase $crearExpediente,
        private PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    #[Route(path: '', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $expedientes = ($this->listarExpedientes)();

        return new JsonResponse(array_map(
            $this->expedienteToResponse(...),
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
            $this->expedienteToResponse($expediente),
            JsonResponse::HTTP_CREATED,
        );
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
                'pdfUrl' => $p->pdfPath() ? '/api/expedientes/' . $id . '/invoices/' . $p->id()->value() . '/pdf' : null,
                'createdAt' => $p->createdAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return new JsonResponse($list);
    }

    private function expedienteToResponse(Expediente $expediente): ExpedienteResponse
    {
        return new ExpedienteResponse(
            id: $expediente->id()->value(),
            numero: $expediente->numero(),
            titulo: $expediente->titulo(),
            estado: $expediente->estado()->value,
            fechaApertura: $expediente->fechaApertura()->format(\DateTimeInterface::ATOM),
            clientName: $expediente->clientName(),
            caseReference: $expediente->caseReference(),
            folderPath: $expediente->folderPath(),
            paymentStatus: $expediente->paymentStatus(),
        );
    }
}
