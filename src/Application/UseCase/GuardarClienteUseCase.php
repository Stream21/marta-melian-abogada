<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ClienteInput;
use App\Application\Service\EmailValidator;
use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\Cliente;
use App\Domain\Exception\TelefonoClienteDuplicadoException;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;

final class GuardarClienteUseCase
{
    public function __construct(
        private ClienteRepositoryInterface $repository,
        private TelefonoNormalizer $telefonoNormalizer,
        private EmailValidator $emailValidator,
    ) {
    }

    public function __invoke(?string $id, ClienteInput $input, bool $altaMinima = false): Cliente
    {
        $nombre = trim($input->nombre);
        if ('' === $nombre && !$altaMinima) {
            throw new \InvalidArgumentException('El nombre del cliente es obligatorio.');
        }
        if ('' === $nombre && $altaMinima) {
            $nombre = 'Cliente pendiente';
        }

        $telefono = $this->telefonoNormalizer->normalize($input->telefono);
        if ($altaMinima && null === $telefono) {
            throw new \InvalidArgumentException('El teléfono del cliente es obligatorio.');
        }

        $this->emailValidator->assertValid($input->email);

        $this->assertTelefonoUnico($telefono, $id);

        $fechaNacimiento = null;
        if (null !== $input->fechaNacimiento && '' !== trim($input->fechaNacimiento)) {
            $fechaNacimiento = \DateTimeImmutable::createFromFormat('Y-m-d', $input->fechaNacimiento)
                ?: throw new \InvalidArgumentException('Fecha de nacimiento no válida.');
        }

        if (null !== $id && '' !== $id) {
            $existing = $this->repository->findById(new ClienteId($id));
            if (null === $existing) {
                throw new \InvalidArgumentException('Cliente no encontrado.');
            }
            $cliente = $existing->withDatos(
                $nombre,
                trim($input->nacionalidad),
                trim($input->tipoDocumento),
                trim($input->numDocumento),
                $fechaNacimiento,
                trim($input->lugarNacimiento),
                trim($input->domicilio),
                trim($input->codigoPostal),
                trim($input->ciudad),
                $telefono ?? '',
                trim($input->email),
            );
        } else {
            $cliente = new Cliente(
                ClienteId::generate(),
                $nombre,
                trim($input->nacionalidad),
                trim($input->tipoDocumento),
                trim($input->numDocumento),
                $fechaNacimiento,
                trim($input->lugarNacimiento),
                trim($input->domicilio),
                trim($input->codigoPostal),
                trim($input->ciudad),
                $telefono ?? '',
                trim($input->email),
            );
        }

        $this->repository->save($cliente);

        return $cliente;
    }

    private function assertTelefonoUnico(?string $telefono, ?string $excludeId): void
    {
        if (null === $telefono) {
            return;
        }

        $existente = $this->repository->findByTelefono($telefono);
        if (null === $existente) {
            return;
        }

        if (null !== $excludeId && $excludeId === $existente->id()->value()) {
            return;
        }

        throw new TelefonoClienteDuplicadoException(
            $telefono,
            $existente->id()->value(),
            $existente->nombre(),
        );
    }
}
