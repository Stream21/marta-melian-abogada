<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Holded sync fields to payment table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE payment ADD COLUMN IF NOT EXISTS holded_estado VARCHAR(20) NOT NULL DEFAULT 'no_aplica'");
        $this->addSql('ALTER TABLE payment ADD COLUMN IF NOT EXISTS holded_sync_error TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD COLUMN IF NOT EXISTS holded_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("UPDATE payment SET holded_estado = 'sincronizado' WHERE holded_invoice_id IS NOT NULL AND holded_invoice_id != ''");
        $this->addSql("UPDATE payment SET holded_estado = 'pendiente_sync' WHERE status = 'paid' AND type IN ('link', 'installment') AND (holded_invoice_id IS NULL OR holded_invoice_id = '')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP COLUMN IF EXISTS holded_estado');
        $this->addSql('ALTER TABLE payment DROP COLUMN IF EXISTS holded_sync_error');
        $this->addSql('ALTER TABLE payment DROP COLUMN IF EXISTS holded_synced_at');
    }
}
