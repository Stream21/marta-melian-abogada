<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\UseCase\VerificarVencimientosExpedienteUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:expedientes:verificar-vencimientos', description: 'Alertas de vencimiento de fase al cliente (7 / 3 / 0 días restantes)')]
final class VerificarVencimientosExpedienteCommand extends Command
{
    public function __construct(
        private VerificarVencimientosExpedienteUseCase $useCase,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $alertas = ($this->useCase)();
        $io->success(sprintf('Procesados. Alertas encoladas: %d', $alertas));

        return Command::SUCCESS;
    }
}
