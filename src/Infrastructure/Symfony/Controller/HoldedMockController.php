<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Alias legacy: /api/mock/holded/v1/* → /api/mock/holded/invoicing/v1/*
 * Mantiene compatibilidad con clientes antiguos durante la unificación.
 */
#[Route(path: '/api/mock/holded/v1', name: 'api_mock_holded_legacy_')]
final class HoldedMockController extends AbstractController
{
    #[Route(path: '/contacts', name: 'contacts', methods: ['GET', 'POST'])]
    public function contacts(Request $request, HttpKernelInterface $httpKernel): JsonResponse
    {
        return $this->forwardToInvoicing($request, $httpKernel, '/api/mock/holded/invoicing/v1/contacts');
    }

    #[Route(path: '/documents/invoice', name: 'invoice_create', methods: ['POST'])]
    public function createInvoice(Request $request, HttpKernelInterface $httpKernel): JsonResponse
    {
        return $this->forwardToInvoicing($request, $httpKernel, '/api/mock/holded/invoicing/v1/documents/invoice');
    }

    #[Route(path: '/documents/invoice/{id}/pdf', name: 'invoice_pdf', methods: ['GET'])]
    public function pdf(Request $request, HttpKernelInterface $httpKernel, string $id): JsonResponse
    {
        $sub = Request::create(
            '/api/mock/holded/invoicing/v1/documents/invoice/' . $id . '/pdf?format=json',
            'GET',
            [],
            $request->cookies->all(),
            [],
            $request->server->all(),
        );
        foreach ($request->headers->all() as $key => $values) {
            $sub->headers->set($key, $values);
        }

        $response = $httpKernel->handle($sub, HttpKernelInterface::SUB_REQUEST);
        $content = $response->getContent();
        $data = is_string($content) ? json_decode($content, true) : null;

        return new JsonResponse(
            is_array($data) ? $data : ['error' => 'forward failed'],
            $response->getStatusCode(),
        );
    }

    #[Route(path: '/documents/invoice/{id}/pay', name: 'invoice_pay', methods: ['POST'])]
    public function pay(Request $request, HttpKernelInterface $httpKernel, string $id): JsonResponse
    {
        return $this->forwardToInvoicing(
            $request,
            $httpKernel,
            '/api/mock/holded/invoicing/v1/documents/invoice/' . $id . '/pay',
        );
    }

    private function forwardToInvoicing(Request $request, HttpKernelInterface $httpKernel, string $path): JsonResponse
    {
        $sub = Request::create(
            $path,
            $request->getMethod(),
            [],
            $request->cookies->all(),
            [],
            $request->server->all(),
            $request->getContent(),
        );
        foreach ($request->headers->all() as $key => $values) {
            $sub->headers->set($key, $values);
        }

        $response = $httpKernel->handle($sub, HttpKernelInterface::SUB_REQUEST);
        $content = $response->getContent();
        $data = is_string($content) && '' !== $content ? json_decode($content, true) : [];

        return new JsonResponse(is_array($data) ? $data : [], $response->getStatusCode());
    }
}
