<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\DespachoMembreteDefaults;
use App\Domain\Entity\DespachoConfig;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Plantillas HTML de cabecera (LOGO_DESPACHO) y pie con ondas';
    }

    public function up(Schema $schema): void
    {
        $cabecera = $this->connection->quote(DespachoMembreteDefaults::cabeceraHtml());
        $pie = $this->connection->quote(DespachoMembreteDefaults::pieHtml());
        $id = $this->connection->quote(DespachoConfig::DEFAULT_ID);

        $this->addSql(sprintf(
            'UPDATE despacho_config SET cabecera_html = %s, pie_html = %s WHERE id = %s',
            $cabecera,
            $pie,
            $id,
        ));
    }

    public function down(Schema $schema): void
    {
        $id = $this->connection->quote(DespachoConfig::DEFAULT_ID);
        $this->addSql(sprintf(
            'UPDATE despacho_config SET cabecera_html = NULL, pie_html = NULL WHERE id = %s',
            $id,
        ));
    }
}
