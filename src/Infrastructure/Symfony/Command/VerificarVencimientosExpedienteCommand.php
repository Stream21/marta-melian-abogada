<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\Port\EmailPort;
use App\Application\Port\TwilioPort;
use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\FaseNegocioExpediente;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:expedientes:verificar-vencimientos', description: 'Alertas de vencimiento de fase en expedientes activos')]
final class VerificarVencimientosExpedienteCommand extends Command
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private EmailPort $emailPort,
        private TwilioPort $twilioPort,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable('today');
        $alertas = 0;

        foreach ($this->expedienteRepository->findAll() as $expediente) {
            if ($expediente->estado() !== EstadoExpediente::Abierto) {
                continue;
            }

            if ($expediente->faseNegocio() === FaseNegocioExpediente::Resolucion) {
                continue;
            }

            $vencimiento = $expediente->fechaVencimientoFase();
            if (null === $vencimiento) {
                continue;
            }

            $dias = (int) $now->diff($vencimiento)->format('%r%a');
            if (!in_array($dias, [-7, -5, -1, 0], true)) {
                continue;
            }

            $mensaje = sprintf(
                'Recordatorio expediente %s (%s): vence en %d día(s).',
                $expediente->numero(),
                $expediente->clientName(),
                max(0, $dias),
            );

            $this->emailPort->send('abogado@bufete.local', 'Vencimiento de fase', $mensaje);
            ++$alertas;
        }

        $io->success(sprintf('Procesados. Alertas enviadas: %d', $alertas));

        return Command::SUCCESS;
    }
}
