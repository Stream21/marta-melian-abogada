<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\ClienteInput;
use App\Application\Port\ClienteFileStoragePort;
use App\Application\Port\DocumentoIdentidadExtractorPort;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\TipoEscaneoDocumentoIdentidad;
use App\Domain\Repository\ClienteRepositoryInterface;

final class CrearClienteConDocumentoUseCase
{
    public function __construct(
        private GuardarClienteUseCase $guardarCliente,
        private ClienteRepositoryInterface $clienteRepository,
        private ClienteFileStoragePort $fileStorage,
        private DocumentoIdentidadExtractorPort $extractor,
    ) {
    }

    public function __invoke(
        string $tipoEscaneo,
        string $anversoBinary,
        ?string $reversoBinary,
        ClienteInput $input,
    ): Cliente {
        $tipo = TipoEscaneoDocumentoIdentidad::tryFrom($tipoEscaneo)
            ?? throw new \InvalidArgumentException('Tipo de escaneo no válido.');

        if ('' === $anversoBinary) {
            throw new \InvalidArgumentException('El documento de identidad (anverso) es obligatorio.');
        }

        if ($tipo->requiereReverso() && (null === $reversoBinary || '' === $reversoBinary)) {
            throw new \InvalidArgumentException('El reverso del documento es obligatorio para DNI/NIE.');
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

        $merged = new ClienteInput(
            nombre: $this->priorizar($input->nombre, (string) $extraidos['nombre']),
            nacionalidad: $this->priorizar($input->nacionalidad, (string) $extraidos['nacionalidad']),
            tipoDocumento: $this->priorizar($input->tipoDocumento, (string) $extraidos['tipoDocumento']),
            numDocumento: $this->priorizar($input->numDocumento, (string) $extraidos['numDocumento']),
            fechaNacimiento: $input->fechaNacimiento ?? $extraidos['fechaNacimiento'],
            lugarNacimiento: $this->priorizar($input->lugarNacimiento, (string) $extraidos['lugarNacimiento']),
            estadoCivil: trim($input->estadoCivil),
            domicilio: $this->priorizar($input->domicilio, (string) $extraidos['domicilio']),
            codigoPostal: $this->priorizar($input->codigoPostal, (string) $extraidos['codigoPostal']),
            ciudad: $this->priorizar($input->ciudad, (string) $extraidos['ciudad']),
            provincia: $this->priorizar($input->provincia, (string) $extraidos['provincia']),
            nombrePadre: $this->priorizar($input->nombrePadre, (string) $extraidos['nombrePadre']),
            nombreMadre: $this->priorizar($input->nombreMadre, (string) $extraidos['nombreMadre']),
            telefono: $input->telefono,
            email: $input->email,
        );

        if ('' === trim($merged->numDocumento)) {
            throw new \InvalidArgumentException('Indique el número de documento tras el escaneo.');
        }

        if ('' === trim($merged->nombre)) {
            throw new \InvalidArgumentException('Indique el nombre completo tras el escaneo.');
        }

        $cliente = ($this->guardarCliente)(null, $merged);
        $anversoPath = $this->fileStorage->saveDocumentoIdentidad($cliente->id(), 'anverso.jpg', $anversoBinary);
        $reversoPath = null;
        if (null !== $reversoBinary && '' !== $reversoBinary) {
            $reversoPath = $this->fileStorage->saveDocumentoIdentidad($cliente->id(), 'reverso.jpg', $reversoBinary);
        }

        $clienteConDoc = $cliente->withDocumentoIdentidad($tipo, $anversoPath, $reversoPath);
        $this->clienteRepository->save($clienteConDoc);

        return $clienteConDoc;
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
