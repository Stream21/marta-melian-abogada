<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Entity\AreaJuridicaCatalog;
use App\Domain\Entity\Servicio;
use App\Domain\Entity\TipoServicio;
use App\Domain\Entity\PlataformaTramitacion;
use App\Domain\Entity\Tramite;
use App\Domain\Repository\ServicioRepositoryInterface;
use App\Domain\Repository\TramiteRepositoryInterface;
use App\Domain\ValueObject\ServicioId;
use App\Domain\ValueObject\TramiteId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-servicios-tramites',
    description: 'Inserta servicios y trámites de ejemplo (desarrollo / demos).',
)]
final class SeedServiciosTramitesCommand extends Command
{
    public function __construct(
        private ServicioRepositoryInterface $servicioRepository,
        private TramiteRepositoryInterface $tramiteRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'reset',
                null,
                InputOption::VALUE_NONE,
                'Desactiva todos los servicios y trámites existentes antes de insertar (no borra registros).',
            )
            ->addOption(
                'no-skip-existing',
                null,
                InputOption::VALUE_NONE,
                'Inserta todos los registros aunque el nombre ya exista.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $skipExisting = !$input->getOption('no-skip-existing');

        if ($input->getOption('reset')) {
            if (!$io->confirm('¿Desactivar todos los servicios y trámites existentes?', false)) {
                $io->warning('Operación cancelada.');

                return Command::SUCCESS;
            }
            foreach ($this->tramiteRepository->findAll(true) as $tramite) {
                if ($tramite->activo()) {
                    $this->tramiteRepository->save($tramite->withActivo(false));
                }
            }
            foreach ($this->servicioRepository->findAll(true) as $servicio) {
                if ($servicio->activo()) {
                    $this->servicioRepository->save($servicio->withActivo(false));
                }
            }
            $io->info('Servicios y trámites desactivados.');
        }

        $serviciosExistentes = [];
        if ($skipExisting) {
            foreach ($this->servicioRepository->findAll(true) as $s) {
                $serviciosExistentes[mb_strtolower(trim($s->nombre()))] = $s;
            }
        }

        $tramitesExistentes = [];
        if ($skipExisting) {
            foreach ($this->tramiteRepository->findAll(true) as $t) {
                $clave = $t->servicioId()->value() . ':' . mb_strtolower(trim($t->nombre()));
                $tramitesExistentes[$clave] = true;
            }
        }

        $serviciosCreados = 0;
        $tramitesCreados = 0;
        $omitidos = 0;

        foreach (self::definicionesSeed() as $defServicio) {
            $nombreServicio = $defServicio['nombre'];
            $claveServicio = mb_strtolower(trim($nombreServicio));

            if ($skipExisting && isset($serviciosExistentes[$claveServicio])) {
                $servicio = $serviciosExistentes[$claveServicio];
                ++$omitidos;
            } else {
                $tipo = TipoServicio::fromString($defServicio['tipo']);
                $servicio = new Servicio(
                    ServicioId::generate(),
                    $nombreServicio,
                    AreaJuridicaCatalog::idFromCodigo($tipo->value),
                    true,
                    $tipo->value,
                    $tipo->label(),
                );
                $this->servicioRepository->save($servicio);
                $serviciosExistentes[$claveServicio] = $servicio;
                ++$serviciosCreados;
            }

            foreach ($defServicio['tramites'] as $defTramite) {
                $claveTramite = $servicio->id()->value() . ':' . mb_strtolower(trim($defTramite['nombre']));
                if ($skipExisting && isset($tramitesExistentes[$claveTramite])) {
                    ++$omitidos;

                    continue;
                }

                $this->tramiteRepository->save(
                    new Tramite(
                        TramiteId::generate(),
                        $servicio->id(),
                        $defTramite['nombre'],
                        $defTramite['honorarios'],
                        PlataformaTramitacion::fromString($defTramite['plataforma'] ?? 'mercurio'),
                        $defTramite['requiere_procurador'] ?? false,
                        true,
                        $servicio->nombre(),
                    ),
                );
                $tramitesExistentes[$claveTramite] = true;
                ++$tramitesCreados;
            }
        }

        $io->success(sprintf('Servicios insertados: %d. Trámites insertados: %d.', $serviciosCreados, $tramitesCreados));
        if ($omitidos > 0) {
            $io->note(sprintf('Registros omitidos por ya existir: %d.', $omitidos));
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<array{nombre: string, tipo: string, tramites: list<array{nombre: string, honorarios: float}>}>
     */
    private static function definicionesSeed(): array
    {
        return [
            [
                'nombre' => 'Derecho Civil',
                'tipo' => TipoServicio::CivilContratacion->value,
                'tramites' => [
                    ['nombre' => 'Obligaciones y contratos', 'honorarios' => 850.0],
                    ['nombre' => 'Propiedad y derecho real', 'honorarios' => 950.0],
                    ['nombre' => 'Arrendamientos urbanos', 'honorarios' => 750.0],
                ],
            ],
            [
                'nombre' => 'Extranjería y Nacionalidad',
                'tipo' => TipoServicio::ExtranjeriaNacionalidad->value,
                'tramites' => [
                    ['nombre' => 'Residencia y trabajo', 'honorarios' => 1200.0],
                    ['nombre' => 'Nacionalidad española', 'honorarios' => 1500.0],
                    ['nombre' => 'Asilo y protección', 'honorarios' => 1800.0],
                ],
            ],
            [
                'nombre' => 'Derecho Penal',
                'tipo' => TipoServicio::Penal->value,
                'tramites' => [
                    ['nombre' => 'Defensa general', 'honorarios' => 2000.0],
                    ['nombre' => 'Violencia de género', 'honorarios' => 1500.0],
                    ['nombre' => 'Delitos económicos', 'honorarios' => 2500.0],
                ],
            ],
            [
                'nombre' => 'Derecho Laboral',
                'tipo' => TipoServicio::LaboralSeguridadSocial->value,
                'tramites' => [
                    ['nombre' => 'Despidos', 'honorarios' => 900.0],
                    ['nombre' => 'Modificación sustancial', 'honorarios' => 800.0],
                    ['nombre' => 'Accidentes laborales', 'honorarios' => 1100.0],
                ],
            ],
            [
                'nombre' => 'Derecho de Familia',
                'tipo' => TipoServicio::FamiliaSucesiones->value,
                'tramites' => [
                    ['nombre' => 'Separación y divorcio', 'honorarios' => 1300.0],
                    ['nombre' => 'Sucesiones y testamentos', 'honorarios' => 1600.0],
                    ['nombre' => 'Capacidad y tutelas', 'honorarios' => 1000.0],
                ],
            ],
            [
                'nombre' => 'Derecho Administrativo',
                'tipo' => TipoServicio::CivilContratacion->value,
                'tramites' => [
                    ['nombre' => 'Contencioso-administrativo', 'honorarios' => 1400.0],
                    ['nombre' => 'Urbanismo y licencias', 'honorarios' => 1100.0],
                    ['nombre' => 'Contratación pública', 'honorarios' => 1700.0],
                ],
            ],
        ];
    }
}
