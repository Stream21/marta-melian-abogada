<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ClienteInput;
use App\Application\Service\ClienteEdicionPolicy;
use App\Application\Service\ClienteUnicidadValidator;
use App\Application\Service\DocumentoIdentidadNormalizer;
use App\Application\Service\EmailValidator;
use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\Cliente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;

final class GuardarClienteUseCase
{
    public function __construct(
        private ClienteRepositoryInterface $repository,
        private TelefonoNormalizer $telefonoNormalizer,
        private DocumentoIdentidadNormalizer $documentoNormalizer,
        private EmailValidator $emailValidator,
        private ClienteEdicionPolicy $edicionPolicy,
        private ClienteUnicidadValidator $unicidadValidator,
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
        if (null === $telefono || !$this->telefonoNormalizer->isValid($telefono)) {
            throw new \InvalidArgumentException('El teléfono móvil del cliente es obligatorio y debe ser válido.');
        }

        $numDocumento = $this->documentoNormalizer->normalize($input->numDocumento);

        $this->emailValidator->assertValid($input->email);

        $this->unicidadValidator->assertTelefonoUnico($telefono, $id);
        $this->unicidadValidator->assertDocumentoUnico($numDocumento, $id);

        $fechaNacimiento = null;
        if (null !== $input->fechaNacimiento && '' !== trim($input->fechaNacimiento)) {
            $fechaNacimiento = \DateTimeImmutable::createFromFormat('Y-m-d', $input->fechaNacimiento)
                ?: throw new \InvalidArgumentException('Fecha de nacimiento no válida.');
        }

        if (null !== $id && '' !== $id) {
            $clienteId = new ClienteId($id);
            $existing = $this->repository->findById($clienteId);
            if (null === $existing) {
                throw new \InvalidArgumentException('Cliente no encontrado.');
            }

            if (!$this->edicionPolicy->puedeEditar($clienteId)) {
                throw new \InvalidArgumentException(
                    $this->edicionPolicy->motivoBloqueo($clienteId)
                    ?? 'No se pueden modificar los datos de este cliente en este momento.',
                );
            }
            $cliente = $existing->withDatos(
                $nombre,
                trim($input->nacionalidad),
                trim($input->tipoDocumento),
                $numDocumento,
                $fechaNacimiento,
                trim($input->lugarNacimiento),
                trim($input->estadoCivil),
                trim($input->domicilio),
                trim($input->codigoPostal),
                trim($input->ciudad),
                trim($input->provincia),
                trim($input->nombrePadre),
                trim($input->nombreMadre),
                $telefono,
                trim($input->email),
            );
        } else {
            $cliente = new Cliente(
                ClienteId::generate(),
                $nombre,
                trim($input->nacionalidad),
                trim($input->tipoDocumento),
                $numDocumento,
                $fechaNacimiento,
                trim($input->lugarNacimiento),
                trim($input->estadoCivil),
                trim($input->domicilio),
                trim($input->codigoPostal),
                trim($input->ciudad),
                trim($input->provincia),
                trim($input->nombrePadre),
                trim($input->nombreMadre),
                $telefono,
                trim($input->email),
            );
        }

        $this->repository->save($cliente);

        return $cliente;
    }
}
