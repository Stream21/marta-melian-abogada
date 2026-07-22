<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, method: 'onRequest', priority: 100)]
#[AsEventListener(event: KernelEvents::RESPONSE, method: 'onResponse')]
final class CorsListener
{
    private const string ALLOW_HEADERS = 'Content-Type, Authorization, ngrok-skip-browser-warning';

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
        $this->applyCorsHeaders($response, $origin);
        $event->setResponse($response);
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $origin = $request->headers->get('Origin', '');
        if ($origin === '' || !$this->isAllowedOrigin($origin)) {
            return;
        }

        $this->applyCorsHeaders($event->getResponse(), $origin);
    }

    private function applyCorsHeaders(Response $response, string $origin): void
    {
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', self::ALLOW_HEADERS);
    }

    private function isAllowedOrigin(string $origin): bool
    {
        if (
            str_starts_with($origin, 'http://localhost:')
            || str_starts_with($origin, 'http://127.0.0.1:')
        ) {
            return true;
        }

        $host = parse_url($origin, \PHP_URL_HOST);
        if (is_string($host) && (
            str_ends_with($host, '.ngrok-free.dev')
            || str_ends_with($host, '.ngrok-free.app')
            || str_ends_with($host, '.ngrok.io')
        )) {
            return true;
        }

        foreach ($this->configuredOrigins() as $allowed) {
            if ($origin === $allowed) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function configuredOrigins(): array
    {
        $origins = [];

        foreach (['FRONTEND_BASE_URL', 'MERCURE_CORS_ORIGINS', 'NGROK_FRONT_URL'] as $key) {
            $raw = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
            if (false === $raw || null === $raw || '' === trim((string) $raw)) {
                continue;
            }

            foreach (preg_split('/\s+/', trim((string) $raw)) ?: [] as $part) {
                $part = rtrim($part, '/');
                if ('' !== $part) {
                    $origins[] = $part;
                }
            }
        }

        return array_values(array_unique($origins));
    }
}
