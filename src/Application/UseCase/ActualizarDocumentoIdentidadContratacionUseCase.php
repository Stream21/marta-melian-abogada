<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ClienteInput;
use App\Application\Port\ClienteFileStoragePort;
use App\Application\Port\ContratacionRealtimePort;
use App\Application\Port\DocumentoIdentidadExtractorPort;
use App\Application\Service\ClienteUnicidadValidator;
use App\Application\Service\DocumentoIdentidadNormalizer;
use App\Application\Service\EmailValidator;
use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\TipoEscaneoDocumentoIdentidad;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;
use App\Domain\ValueObject\ExpedienteId;

/**
 * Carga directa del documento de identidad por el abogado durante la contratación.
 * El cliente en el portal debe usar el flujo de captura con cámara/OCR.
 */
final class ActualizarDocumentoIdentidadContratacionUseCase
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private ClienteRepositoryInterface $clienteRepository,
        private ContratacionRepositoryInterface $contratacionRepository,
        private ClienteFileStoragePort $fileStorage,
        private DocumentoIdentidadExtractorPort $extractor,
        private ContratacionRealtimePort $realtime,
        private EmailValidator $emailValidator,
        private TelefonoNormalizer $telefonoNormalizer,
        private DocumentoIdentidadNormalizer $documentoNormalizer,
        private ClienteUnicidadValidator $unicidadValidator,
    ) {
    }

    public function __invoke(
        string $expedienteId,
        string $tipoEscaneo,
        string $anversoBinary,
        ?string $reversoBinary,
        ClienteInput $input,
    ): void {
        $expediente = $this->expedienteRepository->findById(new ExpedienteId($expedienteId));
        if (null === $expediente) {
            throw new \InvalidArgumentException('Expediente no encontrado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Solo puede cargar el documento durante la fase de contratación.');
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

        $tipo = TipoEscaneoDocumentoIdentidad::tryFrom($tipoEscaneo)
            ?? throw new \InvalidArgumentException('Tipo de escaneo no válido.');

        if ('' === $anversoBinary) {
            throw new \InvalidArgumentException('Debe adjuntar la imagen del anverso del documento.');
        }

        if ($tipo->requiereReverso() && (null === $reversoBinary || '' === $reversoBinary)) {
            throw new \InvalidArgumentException('El reverso del documento es obligatorio para DNI/NIE.');
        }

        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            throw new \InvalidArgumentException('Cliente no encontrado.');
        }

        $anversoTemp = $this->writeTemp($anversoBinary, 'anverso');
        $reversoTemp = null !== $reversoBinary && '' !== $reversoBinary
            ? $this->writeTemp($reversoBinary, 'reverso')
            : null;

        try {
            $extraidos = $this->extractor->extract($tipo->value, $anversoTemp, $reversoTemp);
        } finally {
            @unlink($anversoTemp);
            if (null !== $reversoTemp) {
                @unlink($reversoTemp);
            }
        }

        $nombre = $this->priorizar($input->nombre, (string) $extraidos['nombre'], $cliente->nombre());
        $numDocumento = $this->documentoNormalizer->normalize(
            $this->priorizar($input->numDocumento, (string) $extraidos['numDocumento'], $cliente->numDocumento()),
        );

        if ('' === $nombre) {
            throw new \InvalidArgumentException('Indique el nombre completo del cliente.');
        }

        if ('' === $numDocumento) {
            throw new \InvalidArgumentException('Indique el número de documento.');
        }

        $telefono = $this->telefonoNormalizer->normalize(
            '' !== trim($input->telefono) ? $input->telefono : $cliente->telefono(),
        );
        if (null === $telefono || !$this->telefonoNormalizer->isValid($telefono)) {
            throw new \InvalidArgumentException('El teléfono móvil del cliente es obligatorio y debe ser válido.');
        }

        $email = '' !== trim($input->email) ? trim($input->email) : $cliente->email();
        $this->emailValidator->assertValid($email);

        $fechaNacimiento = $cliente->fechaNacimiento();
        if (null !== $input->fechaNacimiento && '' !== trim($input->fechaNacimiento)) {
            $fechaNacimiento = \DateTimeImmutable::createFromFormat('Y-m-d', $input->fechaNacimiento)
                ?: throw new \InvalidArgumentException('Fecha de nacimiento no válida.');
        } elseif (null !== $extraidos['fechaNacimiento'] && '' !== $extraidos['fechaNacimiento']) {
            $fechaNacimiento = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $extraidos['fechaNacimiento']) ?: $fechaNacimiento;
        }

        $this->unicidadValidator->assertTelefonoUnico($telefono, $clienteId);
        $this->unicidadValidator->assertDocumentoUnico($numDocumento, $clienteId);

        $anversoPath = $this->fileStorage->saveDocumentoIdentidad($cliente->id(), 'anverso.jpg', $anversoBinary);
        $reversoPath = null;
        if (null !== $reversoBinary && '' !== $reversoBinary) {
            $reversoPath = $this->fileStorage->saveDocumentoIdentidad($cliente->id(), 'reverso.jpg', $reversoBinary);
        }

        $clienteActualizado = $cliente->withDatos(
            $nombre,
            $this->priorizar($input->nacionalidad, (string) $extraidos['nacionalidad'], $cliente->nacionalidad()),
            $this->priorizar($input->tipoDocumento, (string) $extraidos['tipoDocumento'], $cliente->tipoDocumento()),
            $numDocumento,
            $fechaNacimiento,
            $this->priorizar($input->lugarNacimiento, (string) $extraidos['lugarNacimiento'], $cliente->lugarNacimiento()),
            '' !== trim($input->estadoCivil) ? trim($input->estadoCivil) : $cliente->estadoCivil(),
            $this->priorizar($input->domicilio, (string) $extraidos['domicilio'], $cliente->domicilio()),
            $this->priorizar($input->codigoPostal, (string) $extraidos['codigoPostal'], $cliente->codigoPostal()),
            $this->priorizar($input->ciudad, (string) $extraidos['ciudad'], $cliente->ciudad()),
            $this->priorizar($input->provincia, (string) $extraidos['provincia'], $cliente->provincia()),
            $this->priorizar($input->nombrePadre, (string) $extraidos['nombrePadre'], $cliente->nombrePadre()),
            $this->priorizar($input->nombreMadre, (string) $extraidos['nombreMadre'], $cliente->nombreMadre()),
            $telefono,
            $email,
        )->withDocumentoIdentidad($tipo, $anversoPath, $reversoPath);

        $this->clienteRepository->save($clienteActualizado);

        $this->expedienteRepository->save($expediente->withClientName($nombre));

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            'documento_identidad_cargado_abogado',
            'El abogado ha cargado el documento de identidad del cliente.',
            ActorHitoExpediente::Abogado,
            new \DateTimeImmutable('now'),
            PasoContratacionCliente::DatosCliente,
        ));

        $this->realtime->publishContratacionUpdate($expediente->id()->value(), [
            'type' => 'documento_identidad_actualizado',
            'paso' => PasoContratacionCliente::DatosCliente->value,
            'actor' => 'abogado',
        ]);
    }

    private function writeTemp(string $content, string $suffix): string
    {
        $path = sys_get_temp_dir() . '/doc-id-' . bin2hex(random_bytes(8)) . '-' . $suffix . '.jpg';
        file_put_contents($path, $content);

        return $path;
    }

    private function priorizar(string $manual, string $extraido, string $existente = ''): string
    {
        $manual = trim($manual);
        if ('' !== $manual) {
            return $manual;
        }

        $extraido = trim($extraido);
        if ('' !== $extraido) {
            return $extraido;
        }

        return trim($existente);
    }
}
