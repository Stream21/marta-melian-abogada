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
    /** Campos del documento que el cliente no puede alterar (identidad del soporte). */
    private const CAMPOS_DOCUMENTO_BLOQUEADOS = ['tipoDocumento', 'numDocumento'];

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
        string $token,
        ?string $tipoEscaneo,
        ?string $anversoBinary,
        ?string $reversoBinary,
        ClienteInput $input,
        bool $soloDatos = false,
        bool $permitirDuplicado = false,
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

        $cliente = $this->clienteRepository->findById(new ClienteId($clienteId));
        if (null === $cliente) {
            throw new \InvalidArgumentException('Cliente no encontrado.');
        }

        $tieneDocumentoPrevio = $cliente->tieneDocumentoIdentidad();
        $tipo = $this->resolverTipoEscaneo($tipoEscaneo, $cliente, $soloDatos, $tieneDocumentoPrevio);

        if ($soloDatos) {
            if (!$tieneDocumentoPrevio) {
                throw new \InvalidArgumentException('Debe aportar el documento de identidad antes de continuar.');
            }
        } else {
            $this->validarArchivosObligatorios($tipo, $anversoBinary, $reversoBinary, $cliente);
        }

        $extraidos = ['nombre' => '', 'numDocumento' => '', 'nacionalidad' => '', 'tipoDocumento' => '', 'fechaNacimiento' => null, 'lugarNacimiento' => '', 'domicilio' => '', 'codigoPostal' => '', 'ciudad' => '', 'provincia' => '', 'nombrePadre' => '', 'nombreMadre' => ''];

        if (!$soloDatos) {
            $temporales = [];
            $anversoTemp = $this->resolverBinarioDocumento($anversoBinary, $cliente->documentoIdentidadAnversoPath());
            $temporales[] = $anversoTemp;
            $reversoTemp = $tipo->requiereReverso()
                ? $this->resolverBinarioDocumento($reversoBinary, $cliente->documentoIdentidadReversoPath())
                : null;
            if (null !== $reversoTemp) {
                $temporales[] = $reversoTemp;
            }

            try {
                $extraidos = $this->extractor->extract($tipo->value, $anversoTemp, $reversoTemp);
            } finally {
                foreach ($temporales as $temp) {
                    @unlink($temp);
                }
            }
        }

        $nombre = $this->resolverCampoEditable(
            trim($input->nombre),
            (string) ($extraidos['nombre'] ?? ''),
            $soloDatos ? $cliente->nombre() : '',
        );
        $numDocumento = $this->documentoNormalizer->normalize(
            $this->resolverCampoDocumentoBloqueado('numDocumento', $input, $extraidos, $soloDatos, $cliente),
        );

        if ('' === $nombre) {
            throw new \InvalidArgumentException('Indique su nombre completo.');
        }

        if ('' === $numDocumento) {
            throw new \InvalidArgumentException('Indique su número de documento.');
        }

        $this->emailValidator->assertValid($input->email);

        $fechaNacimiento = $this->resolverFechaNacimientoEditable($input, $extraidos, $soloDatos, $cliente);

        $telefono = $this->telefonoNormalizer->normalize($input->telefono);
        if (null === $telefono || !$this->telefonoNormalizer->isValid($telefono)) {
            throw new \InvalidArgumentException('El teléfono móvil es obligatorio y debe ser válido.');
        }

        $this->unicidadValidator->assertTelefonoUnico($telefono, $clienteId, $permitirDuplicado);
        $this->unicidadValidator->assertDocumentoUnico($numDocumento, $clienteId, $permitirDuplicado);

        $clienteActualizado = $cliente->withDatos(
            $nombre,
            $this->resolverCampoEditable(
                trim($input->nacionalidad),
                (string) ($extraidos['nacionalidad'] ?? ''),
                $soloDatos ? $cliente->nacionalidad() : '',
            ),
            $this->resolverCampoDocumentoBloqueado('tipoDocumento', $input, $extraidos, $soloDatos, $cliente),
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

        $anversoPath = $cliente->documentoIdentidadAnversoPath();
        $reversoPath = $cliente->documentoIdentidadReversoPath();

        if (!$soloDatos) {
            if (null !== $anversoBinary && '' !== $anversoBinary) {
                $anversoPath = $this->fileStorage->saveDocumentoIdentidad($cliente->id(), 'anverso.jpg', $anversoBinary);
            }
            if (null !== $reversoBinary && '' !== $reversoBinary) {
                $reversoPath = $this->fileStorage->saveDocumentoIdentidad($cliente->id(), 'reverso.jpg', $reversoBinary);
            }
        }

        $clienteConDoc = $clienteActualizado->withDocumentoIdentidad($tipo, $anversoPath ?? '', $reversoPath);
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
            $soloDatos
                ? 'El cliente ha corregido sus datos personales.'
                : 'El cliente ha escaneado su documento y confirmado sus datos.',
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

    private function resolverTipoEscaneo(
        ?string $tipoEscaneo,
        \App\Domain\Entity\Cliente $cliente,
        bool $soloDatos,
        bool $tieneDocumentoPrevio,
    ): TipoEscaneoDocumentoIdentidad {
        if ($soloDatos && $tieneDocumentoPrevio && null !== $cliente->documentoIdentidadTipo()) {
            return $cliente->documentoIdentidadTipo();
        }

        return TipoEscaneoDocumentoIdentidad::tryFrom((string) $tipoEscaneo)
            ?? throw new \InvalidArgumentException('Tipo de escaneo no válido.');
    }

    private function validarArchivosObligatorios(
        TipoEscaneoDocumentoIdentidad $tipo,
        ?string $anversoBinary,
        ?string $reversoBinary,
        \App\Domain\Entity\Cliente $cliente,
    ): void {
        $tieneAnverso = (null !== $anversoBinary && '' !== $anversoBinary) || $cliente->tieneDocumentoIdentidad();
        if (!$tieneAnverso) {
            throw new \InvalidArgumentException('El documento de identidad es obligatorio.');
        }

        if ($tipo->requiereReverso()) {
            $tieneReverso = (null !== $reversoBinary && '' !== $reversoBinary)
                || (null !== $cliente->documentoIdentidadReversoPath() && '' !== $cliente->documentoIdentidadReversoPath());
            if (!$tieneReverso) {
                throw new \InvalidArgumentException('El reverso del documento es obligatorio para DNI/NIE.');
            }
        }
    }

    private function resolverBinarioDocumento(?string $binary, ?string $existingRelativePath): string
    {
        if (null !== $binary && '' !== $binary) {
            return $this->writeTemp($binary, 'upload');
        }

        if (null === $existingRelativePath || '' === $existingRelativePath) {
            throw new \InvalidArgumentException('Falta la imagen del documento.');
        }

        $absolute = $this->fileStorage->resolveAbsolutePath($existingRelativePath);
        if (!is_readable($absolute)) {
            throw new \InvalidArgumentException('No se pudo leer el documento de identidad guardado.');
        }

        $content = file_get_contents($absolute);
        if (false === $content || '' === $content) {
            throw new \InvalidArgumentException('No se pudo leer el documento de identidad guardado.');
        }

        return $this->writeTemp($content, 'stored');
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

    /** Prefiere el valor del formulario; si vacío, OCR; si vacío, valor ya guardado. */
    private function resolverCampoEditable(string $manual, string $extraido, string $existente): string
    {
        if ('' !== trim($manual)) {
            return trim($manual);
        }
        if ('' !== trim($extraido)) {
            return trim($extraido);
        }

        return trim($existente);
    }

    /**
     * @param array<string, mixed> $extraidos
     */
    private function resolverCampoDocumentoBloqueado(
        string $campo,
        ClienteInput $input,
        array $extraidos,
        bool $soloDatos,
        \App\Domain\Entity\Cliente $cliente,
    ): string {
        if (!in_array($campo, self::CAMPOS_DOCUMENTO_BLOQUEADOS, true)) {
            return '';
        }

        if ($soloDatos) {
            return match ($campo) {
                'tipoDocumento' => $cliente->tipoDocumento(),
                'numDocumento' => $cliente->numDocumento(),
                default => '',
            };
        }

        $extraido = trim((string) ($extraidos[$campo] ?? ''));
        if ('' !== $extraido) {
            return $extraido;
        }

        return match ($campo) {
            'tipoDocumento' => trim($input->tipoDocumento),
            'numDocumento' => trim($input->numDocumento),
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $extraidos
     */
    private function resolverFechaNacimientoEditable(
        ClienteInput $input,
        array $extraidos,
        bool $soloDatos,
        \App\Domain\Entity\Cliente $cliente,
    ): ?\DateTimeImmutable {
        if (null !== $input->fechaNacimiento && '' !== trim($input->fechaNacimiento)) {
            return \DateTimeImmutable::createFromFormat('Y-m-d', trim($input->fechaNacimiento))
                ?: throw new \InvalidArgumentException('Fecha de nacimiento no válida.');
        }

        if (!$soloDatos && null !== $extraidos['fechaNacimiento'] && '' !== $extraidos['fechaNacimiento']) {
            return \DateTimeImmutable::createFromFormat('Y-m-d', (string) $extraidos['fechaNacimiento']) ?: null;
        }

        return $cliente->fechaNacimiento();
    }
}
