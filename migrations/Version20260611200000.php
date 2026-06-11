<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cliente: documento de identidad escaneado';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cliente ADD documento_identidad_tipo VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD documento_identidad_anverso_path VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD documento_identidad_reverso_path VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD documento_identidad_escaneado_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cliente DROP documento_identidad_tipo');
        $this->addSql('ALTER TABLE cliente DROP documento_identidad_anverso_path');
        $this->addSql('ALTER TABLE cliente DROP documento_identidad_reverso_path');
        $this->addSql('ALTER TABLE cliente DROP documento_identidad_escaneado_at');
    }
}
