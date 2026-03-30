<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Entity\TipoCaso;
use App\Domain\Repository\TipoCasoRepositoryInterface;
use App\Domain\ValueObject\TipoCasoId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-tipos-caso',
    description: 'Inserta un conjunto amplio de tipos de caso de ejemplo (desarrollo / demos).',
)]
final class SeedTiposCasoCommand extends Command
{
    public function __construct(
        private TipoCasoRepositoryInterface $tipoCasoRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'clear',
                null,
                InputOption::VALUE_NONE,
                'Elimina todos los tipos de caso existentes antes de insertar (solo entornos de desarrollo).',
            )
            ->addOption(
                'no-skip-existing',
                null,
                InputOption::VALUE_NONE,
                'Inserta todos los registros aunque el nombre ya exista (por defecto se omiten duplicados por nombre).',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $skipExisting = !$input->getOption('no-skip-existing');

        if ($input->getOption('clear')) {
            if (!$io->confirm('¿Borrar todos los tipos de caso actuales y volver a cargar el seed?', false)) {
                $io->warning('Operación cancelada.');

                return Command::SUCCESS;
            }
            foreach ($this->tipoCasoRepository->findAll() as $existente) {
                $this->tipoCasoRepository->remove($existente);
            }
            $io->info('Tabla tipo_caso vaciada.');
        }

        $existentesPorNombre = [];
        if ($skipExisting) {
            foreach ($this->tipoCasoRepository->findAll() as $t) {
                $existentesPorNombre[mb_strtolower(trim($t->nombre()))] = true;
            }
        }

        $definiciones = self::definicionesSeed();
        $creados = 0;
        $omitidos = 0;

        foreach ($definiciones as $fila) {
            $nombre = $fila['nombre'];
            $clave = mb_strtolower(trim($nombre));
            if ($skipExisting && isset($existentesPorNombre[$clave])) {
                ++$omitidos;

                continue;
            }

            $this->tipoCasoRepository->save(
                new TipoCaso(
                    TipoCasoId::generate(),
                    $nombre,
                    $fila['descripcion'],
                ),
            );
            $existentesPorNombre[$clave] = true;
            ++$creados;
        }

        $io->success(sprintf('Tipos de caso insertados: %d.', $creados));
        if ($omitidos > 0) {
            $io->note(sprintf('Omitidos por nombre ya existente: %d.', $omitidos));
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<array{nombre: string, descripcion: string}>
     */
    private static function definicionesSeed(): array
    {
        return [
            ['nombre' => 'Civil — Obligaciones y contratos', 'descripcion' => 'Reclamaciones por incumplimiento contractual, resolución y daños y perjuicios.'],
            ['nombre' => 'Civil — Propiedad y derecho real', 'descripcion' => 'Deslindes, usucapión, comunidad de bienes y conflictos sobre fincas.'],
            ['nombre' => 'Civil — Responsabilidad civil extracontractual', 'descripcion' => 'Daños por negligencia, accidentes y responsabilidad por hechos ajenos.'],
            ['nombre' => 'Civil — Arrendamientos urbanos', 'descripcion' => 'Impagos, desahucios, actualización de rentas y cláusulas abusivas.'],
            ['nombre' => 'Civil — Propiedad intelectual e industrial', 'descripcion' => 'Marcas, patentes, derechos de autor y competencia desleal.'],
            ['nombre' => 'Penal — Defensa general', 'descripcion' => 'Asistencia letrada en procedimientos penales como acusado o investigado.'],
            ['nombre' => 'Penal — Violencia de género', 'descripcion' => 'Medidas de protección, denuncias y defensa en juicio rápido.'],
            ['nombre' => 'Penal — Delitos económicos', 'descripcion' => 'Estafa, apropiación indebida, alzamiento de bienes y blanqueo.'],
            ['nombre' => 'Penal — Tráfico y seguridad vial', 'descripcion' => 'Delitos contra la seguridad vial y alcoholemia.'],
            ['nombre' => 'Laboral — Despidos', 'descripcion' => 'Impugnación de despidos disciplinarios, objetivos y colectivos.'],
            ['nombre' => 'Laboral — Modificación sustancial', 'descripcion' => 'Cambios de condiciones, movilidad geográfica y funcional.'],
            ['nombre' => 'Laboral — Accidentes y enfermedad profesional', 'descripcion' => 'Reclamaciones a la mutua, incapacidades y responsabilidad empresarial.'],
            ['nombre' => 'Laboral — Inspección y sanciones', 'descripcion' => 'Recursos frente a actas de infracción y multas de la inspección.'],
            ['nombre' => 'Laboral — Negociación colectiva', 'descripcion' => 'Convenios, conflictos colectivos y huelga.'],
            ['nombre' => 'Mercantil — Sociedades', 'descripcion' => 'Constitución, modificaciones estatutarias, conflictos societarios y liquidación.'],
            ['nombre' => 'Mercantil — Concursal', 'descripcion' => 'Concurso de acreedores, administración concursal y acuerdos extrajudiciales.'],
            ['nombre' => 'Mercantil — Contratos mercantiles', 'descripcion' => 'Distribución, agencia, franquicia y suministro.'],
            ['nombre' => 'Mercantil — Títulos valores y pagarés', 'descripcion' => 'Ejecución cambiaria, protestos y reclamación de cantidad.'],
            ['nombre' => 'Mercantil — Competencia y consumo B2B', 'descripcion' => 'Prácticas restrictivas y cláusulas en condiciones generales entre empresas.'],
            ['nombre' => 'Administrativo — Contencioso-administrativo', 'descripcion' => 'Recursos contra actos de la administración pública.'],
            ['nombre' => 'Administrativo — Urbanismo y licencias', 'descripcion' => 'Multas urbanísticas, responsabilidad patrimonial y planeamiento.'],
            ['nombre' => 'Administrativo — Función pública', 'descripcion' => 'Sanciones, ascensos, incompatibilidades y carrera administrativa.'],
            ['nombre' => 'Administrativo — Contratación pública', 'descripcion' => 'Impugnación de pliegos, reclamaciones y recursos especiales.'],
            ['nombre' => 'Administrativo — Medio ambiente', 'descripcion' => 'Sanciones ambientales y responsabilidad por daño ecológico.'],
            ['nombre' => 'Fiscal — Inspección de Hacienda', 'descripcion' => 'Requerimientos, actas de inspección y propuestas de liquidación.'],
            ['nombre' => 'Fiscal — Recursos económico-administrativos', 'descripcion' => 'Reclamaciones y recursos ante el TEAR y el TSJ.'],
            ['nombre' => 'Fiscal — IVA e IGIC', 'descripcion' => 'Regularizaciones, devoluciones y criterios de tributación.'],
            ['nombre' => 'Fiscal — IRPF y renta', 'descripcion' => 'Declaraciones, inspecciones y compensaciones.'],
            ['nombre' => 'Fiscal — Planificación y estructuras', 'descripcion' => 'Reorganizaciones societarias y valoración de operaciones vinculadas.'],
            ['nombre' => 'Familia — Separación y divorcio', 'descripcion' => 'Medidas paterno-filiales, uso de vivienda y compensatorios.'],
            ['nombre' => 'Familia — Filiación y adopciones', 'descripcion' => 'Investigación de paternidad, adopciones nacionales e internacionales.'],
            ['nombre' => 'Familia — Violencia intrafamiliar', 'descripcion' => 'Medidas civiles complementarias y coordinación con vía penal.'],
            ['nombre' => 'Familia — Sucesiones y testamentos', 'descripcion' => 'Herencias, legados, particiones y impugnación de testamentos.'],
            ['nombre' => 'Familia — Capacidad y tutelas', 'descripcion' => 'Curatela, guarda de hecho y modificaciones de capacidad.'],
            ['nombre' => 'Extranjería — Residencia y trabajo', 'descripcion' => 'Autorizaciones de residencia, arraigo y reagrupación familiar.'],
            ['nombre' => 'Extranjería — Nacionalidad', 'descripcion' => 'Expedientes por residencia, opción y carta de naturaleza.'],
            ['nombre' => 'Extranjería — Asilo y protección internacional', 'descripcion' => 'Solicitudes de asilio, refugio y protección subsidiaria.'],
            ['nombre' => 'Extranjería — Devoluciones y frontera', 'descripcion' => 'Expulsiones, prohibiciones de entrada y recursos en frontera.'],
            ['nombre' => 'Consumo — B2C y garantías', 'descripcion' => 'Reclamaciones por productos defectuosos y servicios a consumidores.'],
            ['nombre' => 'Compliance — RGPD y protección de datos', 'descripcion' => 'Adecuación normativa, brechas de seguridad y reclamaciones ante AEPD.'],
            ['nombre' => 'Digital — Ciberdelincuencia y prueba electrónica', 'descripcion' => 'Delitos informáticos, prueba digital y reputación online.'],
        ];
    }
}
