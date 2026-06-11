<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260610130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Separar membrete en cabecera_html y pie_html';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE despacho_config ADD cabecera_html TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE despacho_config ADD pie_html TEXT DEFAULT NULL');
        $this->addSql('UPDATE despacho_config SET cabecera_html = membrete_html, pie_html = membrete_html WHERE membrete_html IS NOT NULL');
        $this->addSql('ALTER TABLE despacho_config DROP membrete_html');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE despacho_config ADD membrete_html TEXT DEFAULT NULL');
        $this->addSql('UPDATE despacho_config SET membrete_html = cabecera_html WHERE cabecera_html IS NOT NULL');
        $this->addSql('ALTER TABLE despacho_config DROP cabecera_html');
        $this->addSql('ALTER TABLE despacho_config DROP pie_html');
    }
}
