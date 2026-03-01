<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Entity\EstadoExpediente;
use App\Domain\Entity\Expediente;
use App\Domain\Entity\Payment;
use App\Domain\Entity\PaymentStatus;
use App\Domain\Entity\PaymentType;
use App\Domain\Repository\ExpedienteRepositoryInterface;
use App\Domain\Repository\PaymentRepositoryInterface;
use App\Domain\ValueObject\ExpedienteId;
use App\Domain\ValueObject\PaymentId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-mocks',
    description: 'Carga expedientes y pagos de prueba en la base de datos.',
)]
final class LoadMocksCommand extends Command
{
    public function __construct(
        private ExpedienteRepositoryInterface $expedienteRepository,
        private PaymentRepositoryInterface $paymentRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Vaciar tablas payment y expediente antes de cargar (solo para desarrollo)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('purge')) {
            $io->warning('Opción --purge no implementada. Ejecuta las migraciones en modo down/up o trunca las tablas manualmente si necesitas empezar de cero.');
        }

        $expedientes = $this->buildExpedientes();
        foreach ($expedientes as $expediente) {
            $this->expedienteRepository->save($expediente);
        }
        $io->success(sprintf('Creados %d expedientes.', count($expedientes)));

        $payments = $this->buildPayments($expedientes);
        foreach ($payments as $payment) {
            $this->paymentRepository->save($payment);
        }
        $io->success(sprintf('Creados %d pagos.', count($payments)));

        return Command::SUCCESS;
    }

    /**
     * @return Expediente[]
     */
    private function buildExpedientes(): array
    {
        $now = new \DateTimeImmutable('now');
        $base = [
            [
                'numero' => 'EXP-2024-001',
                'titulo' => 'Reclamación de cantidad - López García',
                'estado' => EstadoExpediente::Abierto,
                'clientName' => 'María López García',
                'caseReference' => 'Juzgado 1ª Instancia Madrid 123/2024',
                'folderPath' => 'expedientes/2024/EXP-001',
                'paymentStatus' => 'pending',
            ],
            [
                'numero' => 'EXP-2024-002',
                'titulo' => 'Desahucio por impago - Inmobiliaria Norte',
                'estado' => EstadoExpediente::Abierto,
                'clientName' => 'Inmobiliaria Norte S.L.',
                'caseReference' => 'Juzgado de Primera Instancia 5 Barcelona',
                'folderPath' => 'expedientes/2024/EXP-002',
                'paymentStatus' => 'paid',
            ],
            [
                'numero' => 'EXP-2024-003',
                'titulo' => 'Divorcio de mutuo acuerdo - Familia Ruiz',
                'estado' => EstadoExpediente::Cerrado,
                'clientName' => 'Antonio Ruiz y Carmen Fernández',
                'caseReference' => 'Procedimiento 456/2024',
                'folderPath' => 'expedientes/2024/EXP-003',
                'paymentStatus' => 'paid',
            ],
            [
                'numero' => 'EXP-2024-004',
                'titulo' => 'Accidente laboral - Martínez',
                'estado' => EstadoExpediente::Abierto,
                'clientName' => 'Juan Martínez Pérez',
                'caseReference' => 'SMAC Madrid 789/2024',
                'folderPath' => 'expedientes/2024/EXP-004',
                'paymentStatus' => 'pending',
            ],
            [
                'numero' => 'EXP-2023-012',
                'titulo' => 'Arrendamiento comercial - Local Calle Mayor',
                'estado' => EstadoExpediente::Archivado,
                'clientName' => 'Comercial Calle Mayor S.L.',
                'caseReference' => 'Arrendamiento 2023/12',
                'folderPath' => 'expedientes/2023/EXP-012',
                'paymentStatus' => 'paid',
            ],
        ];

        $expedientes = [];
        foreach ($base as $data) {
            $expedientes[] = new Expediente(
                ExpedienteId::generate(),
                $data['numero'],
                $data['titulo'],
                $data['estado'],
                $now,
                $data['clientName'],
                $data['caseReference'],
                $data['folderPath'],
                $data['paymentStatus'],
            );
        }

        return $expedientes;
    }

    /**
     * @param Expediente[] $expedientes
     * @return Payment[]
     */
    private function buildPayments(array $expedientes): array
    {
        $payments = [];
        $base = new \DateTimeImmutable('-30 days');

        foreach ($expedientes as $index => $expediente) {
            $expId = $expediente->id();
            $created = $base->modify("+{$index} days");
            $updated = $created->modify('+1 hour');

            $payments[] = new Payment(
                PaymentId::generate(),
                $expId,
                PaymentStatus::Paid,
                PaymentType::Manual,
                'holded_inv_mock_' . ($index + 1),
                null,
                '45000',
                $expediente->folderPath() . '/factura_1.pdf',
                $created,
                $updated,
            );

            if ($index < 3) {
                $payments[] = new Payment(
                    PaymentId::generate(),
                    $expId,
                    $index === 0 ? PaymentStatus::Pending : PaymentStatus::Paid,
                    PaymentType::Link,
                    null,
                    $index === 0 ? 'cs_mock_pending_1' : null,
                    '12000',
                    null,
                    $created->modify('+2 days'),
                    $created->modify('+2 days'),
                );
            }
        }

        $payments[] = new Payment(
            PaymentId::generate(),
            $expedientes[1]->id(),
            PaymentStatus::Failed,
            PaymentType::Link,
            null,
            'cs_mock_failed_1',
            '25000',
            null,
            $base->modify('+10 days'),
            $base->modify('+10 days'),
        );

        return $payments;
    }
}
