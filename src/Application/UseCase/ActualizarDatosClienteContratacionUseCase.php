<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ClienteInput;
use App\Application\Port\ContratacionRealtimePort;
use App\Application\Service\ClienteUnicidadValidator;
use App\Application\Service\DocumentoIdentidadNormalizer;
use App\Application\Service\EmailValidator;
use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;

/**
 * Permite al abogado corregir la ficha del cliente durante la revisión de identidad
 * (sin pasar por ClienteEdicionPolicy, que bloquea putCliente en contratación).
 */
final class ActualizarDatosClienteContratacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ContratacionRealtimePort $realtime,
        private EmailValidator $emailValidator,
        private TelefonoNormalizer $telefonoNormalizer,
        private DocumentoIdentidadNormalizer $documentoNormalizer,
        private ClienteUnicidadValidator $unicidadValidator,
    ) {
    }

    public function __invoke(string $expedienteId, ClienteInput $input, bool $permitirDuplicado = false): void
    {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Solo puede editar la ficha durante la fase de contratación.');
        }

        $clienteId = $expediente->clienteId();
        if (null === $clienteId || '' === $clienteId) {
            throw new \InvalidArgumentException('Expediente sin cliente vinculado.');
        }

        $pasoDatos = $this->contratacionRepository->findPaso(
            $expediente->id(),
            PasoContratacionCliente::DatosCliente,
        );
        if (null === $pasoDatos || $pasoDatos->estado() === EstadoPasoContratacion::ValidadoAbogado) {
            throw new \InvalidArgumentException('El paso de identidad ya está validado o no está disponible.');
        }

        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            throw new \InvalidArgumentException('Cliente no encontrado.');
        }

        $nombre = trim($input->nombre);
        if ('' === $nombre) {
            throw new \InvalidArgumentException('Indique el nombre completo del cliente.');
        }

        $numDocumento = $this->documentoNormalizer->normalize(trim($input->numDocumento));
        if ('' === $numDocumento) {
            throw new \InvalidArgumentException('Indique el número de documento.');
        }

        $telefono = $this->telefonoNormalizer->normalize(trim($input->telefono));
        if (null === $telefono || !$this->telefonoNormalizer->isValid($telefono)) {
            throw new \InvalidArgumentException('El teléfono móvil del cliente es obligatorio y debe ser válido.');
        }

        $email = trim($input->email);
        $this->emailValidator->assertValid($email);

        $fechaNacimiento = $cliente->fechaNacimiento();
        if (null !== $input->fechaNacimiento) {
            $trimmedFecha = trim($input->fechaNacimiento);
            if ('' === $trimmedFecha) {
                $fechaNacimiento = null;
            } else {
                $fechaNacimiento = \DateTimeImmutable::createFromFormat('Y-m-d', $trimmedFecha)
                    ?: throw new \InvalidArgumentException('Fecha de nacimiento no válida.');
            }
        }

        $this->unicidadValidator->assertTelefonoUnico($telefono, $clienteId, $permitirDuplicado);
        $this->unicidadValidator->assertDocumentoUnico($numDocumento, $clienteId, $permitirDuplicado);

        $clienteActualizado = $cliente->withDatos(
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
            $email,
        );

        $this->clienteRepository->save($clienteActualizado);
        $this->expedienteRepository->save($expediente->withClientName($nombre));

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            'datos_cliente_corregidos_abogado',
            'El abogado ha corregido datos de la ficha del cliente.',
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
            PasoContratacionCliente::DatosCliente,
        ));

        $this->realtime->publishContratacionUpdate($expediente->id()->value(), [
            'type' => 'datos_cliente_actualizados',
            'paso' => PasoContratacionCliente::DatosCliente->value,
            'actor' => 'abogado',
        ]);
    }
}
