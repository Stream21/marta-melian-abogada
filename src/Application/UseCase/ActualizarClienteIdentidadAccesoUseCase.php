<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ClienteInput;
use App\Application\Port\ClienteFileStoragePort;
use App\Application\Port\ContratacionRealtimePort;
use App\Application\Port\DocumentoIdentidadExtractorPort;
use App\Application\Service\EmailValidator;
use App\Application\Service\TelefonoNormalizer;
use App\Domain\Entity\ActorHitoExpediente;
use App\Domain\Entity\EstadoFaseExpediente;
use App\Domain\Entity\EstadoPasoContratacion;
use App\Domain\Entity\ExpedienteHito;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Entity\PasoContratacionCliente;
use App\Domain\Entity\TipoEscaneoDocumentoIdentidad;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ContratacionRepositoryInterface;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\ValueObject\ClienteId;

final class ActualizarClienteIdentidadAccesoUseCase
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
    ) {
    }

    public function __invoke(
        string $token,
        string $tipoEscaneo,
        string $anversoBinary,
        ?string $reversoBinary,
        ClienteInput $input,
    ): void {
        $expediente = $this->expedienteRepository->findByAccessToken($token);
        if (null === $expediente) {
            throw new \InvalidArgumentException('Enlace de acceso no válido o expirado.');
        }

        if ($expediente->faseNegocio() !== FaseNegocioExpediente::Contratacion) {
            throw new \InvalidArgumentException('Este expediente ya no está en fase de contratación.');
        }

        $clienteId = $expediente->clienteId();
        if (null === $clienteId || '' === $clienteId) {
            throw new \InvalidArgumentException('Expediente sin cliente vinculado.');
        }

        $pasoDatos = $this->contratacionRepository->findPaso(
            $expediente->id(),
            PasoContratacionCliente::DatosCliente,
        );
        if (null === $pasoDatos || $pasoDatos->estado() !== EstadoPasoContratacion::Pendiente) {
            throw new \InvalidArgumentException('El paso de datos del cliente no está disponible en este momento.');
        }

        $pasoActivo = $this->resolverPasoActivoCliente($expediente->id());
        if (PasoContratacionCliente::DatosCliente !== $pasoActivo) {
            throw new \InvalidArgumentException('Debe completar los pasos anteriores antes de registrar sus datos.');
        }

        $tipo = TipoEscaneoDocumentoIdentidad::tryFrom($tipoEscaneo)
            ?? throw new \InvalidArgumentException('Tipo de escaneo no válido.');

        if ('' === $anversoBinary) {
            throw new \InvalidArgumentException('El documento de identidad es obligatorio.');
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

        $nombre = '' !== trim($input->nombre) ? trim($input->nombre) : trim((string) $extraidos['nombre']);
        $numDocumento = '' !== trim($input->numDocumento) ? trim($input->numDocumento) : trim((string) $extraidos['numDocumento']);

        if ('' === $nombre) {
            throw new \InvalidArgumentException('Indique su nombre completo.');
        }

        if ('' === $numDocumento) {
            throw new \InvalidArgumentException('Indique su número de documento.');
        }

        $this->emailValidator->assertValid($input->email);

        $fechaNacimiento = null;
        if (null !== $input->fechaNacimiento && '' !== trim($input->fechaNacimiento)) {
            $fechaNacimiento = \DateTimeImmutable::createFromFormat('Y-m-d', $input->fechaNacimiento)
                ?: throw new \InvalidArgumentException('Fecha de nacimiento no válida.');
        } elseif (null !== $extraidos['fechaNacimiento'] && '' !== $extraidos['fechaNacimiento']) {
            $fechaNacimiento = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $extraidos['fechaNacimiento']) ?: null;
        }

        $telefono = $this->telefonoNormalizer->normalize($input->telefono);
        if (null === $telefono || !$this->telefonoNormalizer->isValid($telefono)) {
            throw new \InvalidArgumentException('El teléfono móvil es obligatorio y debe ser válido.');
        }

        $clienteActualizado = $cliente->withDatos(
            $nombre,
            $this->priorizar($input->nacionalidad, (string) $extraidos['nacionalidad']),
            $this->priorizar($input->tipoDocumento, (string) $extraidos['tipoDocumento']),
            $numDocumento,
            $fechaNacimiento,
            $this->priorizar($input->lugarNacimiento, (string) $extraidos['lugarNacimiento']),
            trim($input->estadoCivil),
            $this->priorizar($input->domicilio, (string) $extraidos['domicilio']),
            $this->priorizar($input->codigoPostal, (string) $extraidos['codigoPostal']),
            $this->priorizar($input->ciudad, (string) $extraidos['ciudad']),
            $this->priorizar($input->provincia, (string) $extraidos['provincia']),
            $this->priorizar($input->nombrePadre, (string) $extraidos['nombrePadre']),
            $this->priorizar($input->nombreMadre, (string) $extraidos['nombreMadre']),
            $telefono,
            trim($input->email),
        );

        $anversoPath = $this->fileStorage->saveDocumentoIdentidad($cliente->id(), 'anverso.jpg', $anversoBinary);
        $reversoPath = null;
        if (null !== $reversoBinary && '' !== $reversoBinary) {
            $reversoPath = $this->fileStorage->saveDocumentoIdentidad($cliente->id(), 'reverso.jpg', $reversoBinary);
        }

        $clienteConDoc = $clienteActualizado->withDocumentoIdentidad($tipo, $anversoPath, $reversoPath);
        $this->clienteRepository->save($clienteConDoc);

        $this->expedienteRepository->save(
            $expediente
                ->withClientName($nombre)
                ->withEstadoFase(EstadoFaseExpediente::PendienteFirma),
        );

        $this->contratacionRepository->savePaso($pasoDatos->marcarRealizadoCliente());

        $this->contratacionRepository->saveHito(new ExpedienteHito(
            bin2hex(random_bytes(16)),
            $expediente->id(),
            'datos_cliente_registrados',
            'El cliente ha escaneado su documento y confirmado sus datos.',
            ActorHitoExpediente::Cliente,
            new \DateTimeImmutable('now'),
            PasoContratacionCliente::DatosCliente,
        ));

        $this->realtime->publishContratacionUpdate($expediente->id()->value(), [
            'type' => 'paso_completado',
            'paso' => PasoContratacionCliente::DatosCliente->value,
            'actor' => 'cliente',
        ]);
    }

    private function resolverPasoActivoCliente(\App\Domain\ValueObject\ExpedienteId $expedienteId): ?PasoContratacionCliente
    {
        $pasos = $this->contratacionRepository->findPasosByExpediente($expedienteId);
        $porPaso = [];
        foreach ($pasos as $paso) {
            $porPaso[$paso->paso()->value] = $paso;
        }

        foreach (PasoContratacionCliente::ordenados() as $ordenPaso) {
            $paso = $porPaso[$ordenPaso->value] ?? null;
            if (null === $paso) {
                continue;
            }

            if ($paso->estado() === EstadoPasoContratacion::ValidadoAbogado) {
                continue;
            }

            if ($paso->estado() === EstadoPasoContratacion::RealizadoCliente) {
                return null;
            }

            return $ordenPaso;
        }

        return null;
    }

    private function writeTemp(string $content, string $suffix): string
    {
        $path = sys_get_temp_dir() . '/doc-id-' . bin2hex(random_bytes(8)) . '-' . $suffix . '.jpg';
        file_put_contents($path, $content);

        return $path;
    }

    private function priorizar(string $manual, string $extraido): string
    {
        $manual = trim($manual);

        return '' !== $manual ? $manual : trim($extraido);
    }
}
