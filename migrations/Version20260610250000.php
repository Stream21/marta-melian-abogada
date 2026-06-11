<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\DespachoMembreteDefaults;
use App\Domain\Entity\DespachoConfig;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610250000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pie: ondas elevadas en el gráfico del pie de página';
    }

    public function up(Schema $schema): void
    {
        $pie = $this->connection->quote(DespachoMembreteDefaults::pieHtml());
        $id = $this->connection->quote(DespachoConfig::DEFAULT_ID);

        $this->addSql(sprintf(
            'UPDATE despacho_config SET pie_html = %s WHERE id = %s',
            $pie,
            $id,
        ));
    }

    public function down(Schema $schema): void
    {
    }
}
