<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cliente: estado Holded, contacto y sincronización';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE cliente ADD holded_contact_id VARCHAR(64) DEFAULT NULL");
        $this->addSql("ALTER TABLE cliente ADD holded_estado VARCHAR(20) DEFAULT 'oportunidad' NOT NULL");
        $this->addSql('ALTER TABLE cliente ADD holded_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD holded_sync_error VARCHAR(500) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_CLIENTE_HOLDED_ESTADO ON cliente (holded_estado)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_CLIENTE_HOLDED_ESTADO');
        $this->addSql('ALTER TABLE cliente DROP holded_sync_error');
        $this->addSql('ALTER TABLE cliente DROP holded_synced_at');
        $this->addSql('ALTER TABLE cliente DROP holded_estado');
        $this->addSql('ALTER TABLE cliente DROP holded_contact_id');
    }
}
