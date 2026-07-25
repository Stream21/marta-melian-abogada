<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\UseCase\EnviarRecordatoriosFuturosUseCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:expedientes:enviar-recordatorios-futuros',
    description: 'Envía recordatorios de próximos trámites (cliente + despacho)',
)]
final class EnviarRecordatoriosFuturosCommand extends Command
{
    public function __construct(
        private EnviarRecordatoriosFuturosUseCase $enviarRecordatorios,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $enviados = ($this->enviarRecordatorios)();
        $io->success(sprintf('Recordatorios enviados: %d', $enviados));

        return Command::SUCCESS;
    }
}
