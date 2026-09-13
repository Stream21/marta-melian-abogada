<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260912120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Holded: expediente.holded_invoice_id, cliente.country_code; payment_status admite partial.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente ADD COLUMN IF NOT EXISTS holded_invoice_id VARCHAR(100) DEFAULT NULL');
        $this->addSql("ALTER TABLE cliente ADD COLUMN IF NOT EXISTS country_code VARCHAR(2) DEFAULT 'ES'");
        $this->addSql("UPDATE cliente SET country_code = 'ES' WHERE country_code IS NULL OR country_code = ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente DROP COLUMN IF EXISTS holded_invoice_id');
        $this->addSql('ALTER TABLE cliente DROP COLUMN IF EXISTS country_code');
    }
}
