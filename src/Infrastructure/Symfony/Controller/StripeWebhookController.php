<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use App\Application\UseCase\HandleStripeWebhookUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/webhooks', name: 'api_webhooks_')]
final class StripeWebhookController extends AbstractController
{
    public function __construct(
        private HandleStripeWebhookUseCase $handleStripeWebhook,
    ) {
    }

    #[Route(path: '/stripe', name: 'stripe', methods: ['POST'])]
    public function stripe(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature', '');

        $ok = ($this->handleStripeWebhook)($payload, $signature);

        return new Response('', $ok ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }
}
