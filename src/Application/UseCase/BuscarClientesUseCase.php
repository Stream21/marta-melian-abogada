<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\Cliente;
use App\Domain\Repository\ClienteRepositoryInterface;

final class BuscarClientesUseCase
{
    public function __construct(
        private ClienteRepositoryInterface $clienteRepository,
        private TelefonoNormalizer $telefonoNormalizer,
    ) {
    }

    /**
     * @return array{clientes: array<int, array<string, mixed>>}
     */
    public function __invoke(string $query): array
    {
        $trimmed = trim($query);
        if ('' === $trimmed) {
            return ['clientes' => []];
        }

        // Incluye provisionales: el alta crea ficha solo con teléfono y debe detectarse.
        $clientes = $this->clienteRepository->search($trimmed);

        $telefonoExacto = $this->telefonoNormalizer->normalize($trimmed);
        if (null !== $telefonoExacto) {
            $porTelefono = $this->clienteRepository->findByTelefono($telefonoExacto);
            if (null !== $porTelefono) {
                $clientes = $this->prependUnico($clientes, $porTelefono);
            }
        }

        $email = mb_strtolower($trimmed);
        if (str_contains($email, '@')) {
            foreach ($this->clienteRepository->search($trimmed) as $candidato) {
                if (mb_strtolower(trim($candidato->email())) === $email) {
                    $clientes = $this->prependUnico($clientes, $candidato);
                }
            }
        }

        return [
            'clientes' => array_map($this->clienteToArray(...), array_values($clientes)),
        ];
    }

    /**
     * @param list<Cliente> $clientes
     *
     * @return list<Cliente>
     */
    private function prependUnico(array $clientes, Cliente $candidato): array
    {
        foreach ($clientes as $cliente) {
            if ($cliente->id()->value() === $candidato->id()->value()) {
                return $clientes;
            }
        }

        return [$candidato, ...$clientes];
    }

    /**
     * @return array<string, mixed>
     */
    private function clienteToArray(Cliente $cliente): array
    {
        return [
            'id' => $cliente->id()->value(),
            'nombre' => $cliente->nombre(),
            'telefono' => $cliente->telefono(),
            'email' => $cliente->email(),
            'tipoDocumento' => $cliente->tipoDocumento(),
            'numDocumento' => $cliente->numDocumento(),
            'provisional' => $cliente->esProvisional(),
        ];
    }
}
