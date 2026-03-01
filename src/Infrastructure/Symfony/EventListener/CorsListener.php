<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

#[AsEventListener(event: KernelEvents::REQUEST, method: 'onRequest', priority: 100)]
#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onResponse')]
final class CorsListener
{
    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $event->getRequest()->getMethod() !== 'OPTIONS') {
            return;
        }
        $origin = $event->getRequest()->headers->get('Origin', '');
        if ($origin === '' || !$this->isAllowedOrigin($origin)) {
            return;
        }
        $response = new Response('', 204);
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $event->setResponse($response);
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $origin = $request->headers->get('Origin', '');
        if ($origin === '' || !$this->isAllowedOrigin($origin)) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    private function isAllowedOrigin(string $origin): bool
    {
        return str_starts_with($origin, 'http://localhost:') || str_starts_with($origin, 'http://127.0.0.1:');
    }
}
