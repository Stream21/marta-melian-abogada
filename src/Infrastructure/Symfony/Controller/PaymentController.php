<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\DTO\GenerateLinkRequest;
use App\Application\DTO\ManualPaymentRequest;
use App\Application\DTO\Subscription3Request;
use App\Application\UseCase\CreateManualPaymentUseCase;
use App\Application\UseCase\CreateSubscription3MonthsUseCase;
use App\Application\UseCase\GeneratePaymentLinkUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/payments', name: 'api_payments_')]
final class PaymentController extends AbstractController
{
    public function __construct(
        private CreateManualPaymentUseCase $createManualPayment,
        private GeneratePaymentLinkUseCase $generatePaymentLink,
        private CreateSubscription3MonthsUseCase $createSubscription3Months,
    ) {
    }

    #[Route(path: '/manual', name: 'manual', methods: ['POST'])]
    public function manual(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $input = new ManualPaymentRequest(
            expedienteId: $data['expedienteId'] ?? '',
            amount: (string) ($data['amount'] ?? '0'),
            clientName: $data['clientName'] ?? '',
            caseReference: $data['caseReference'] ?? '',
        );

        $result = ($this->createManualPayment)($input);

        if (!$result->success) {
            return new JsonResponse([
                'success' => false,
                'error' => $result->error,
                'message' => $result->message,
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse([
            'success' => true,
            'paymentId' => $result->paymentId,
            'pdfPath' => $result->pdfPath,
            'pdfUrl' => $result->pdfUrl,
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route(path: '/generate-link', name: 'generate_link', methods: ['POST'])]
    public function generateLink(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $input = new GenerateLinkRequest(
            expedienteId: $data['expedienteId'] ?? '',
            amount: (string) ($data['amount'] ?? '0'),
            phone: $data['phone'] ?? '',
        );

        $result = ($this->generatePaymentLink)($input);

        if (!$result->success) {
            return new JsonResponse([
                'success' => false,
                'error' => $result->error,
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse([
            'success' => true,
            'url' => $result->url,
            'sessionId' => $result->sessionId,
        ]);
    }

    #[Route(path: '/subscription-3', name: 'subscription_3', methods: ['POST'])]
    public function subscription3(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $input = new Subscription3Request(
            expedienteId: $data['expedienteId'] ?? '',
            amount: (string) ($data['amount'] ?? '0'),
        );

        $result = ($this->createSubscription3Months)($input);

        if (isset($result['error'])) {
            return new JsonResponse([
                'success' => false,
                'error' => $result['error'],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse([
            'success' => true,
            'url' => $result['url'],
            'sessionId' => $result['sessionId'],
        ]);
    }
}
