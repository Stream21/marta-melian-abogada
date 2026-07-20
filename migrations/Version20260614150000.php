<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Reparación: asegura columnas Holded en payment si Version20260614140000 quedó registrada sin aplicar SQL.
 */
final class Version20260614150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure payment Holded sync columns exist';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE payment ADD COLUMN IF NOT EXISTS holded_estado VARCHAR(20) NOT NULL DEFAULT 'no_aplica'");
        $this->addSql('ALTER TABLE payment ADD COLUMN IF NOT EXISTS holded_sync_error TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD COLUMN IF NOT EXISTS holded_synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql("UPDATE payment SET holded_estado = 'sincronizado' WHERE holded_estado = 'no_aplica' AND holded_invoice_id IS NOT NULL AND holded_invoice_id != ''");
        $this->addSql("UPDATE payment SET holded_estado = 'pendiente_sync' WHERE holded_estado = 'no_aplica' AND status = 'paid' AND type IN ('link', 'installment') AND (holded_invoice_id IS NULL OR holded_invoice_id = '')");
    }

    public function down(Schema $schema): void
    {
        // No-op: no eliminar columnas en down de reparación
    }
}
