<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Escritos de expediente y cuota en pagos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS expediente_escrito (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            contenido_html TEXT NOT NULL,
            pdf_path VARCHAR(500) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_EXP_ESCRITO_EXPEDIENTE ON expediente_escrito (expediente_id)');

        $this->addSql('ALTER TABLE payment ADD COLUMN IF NOT EXISTS cuota_numero INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP COLUMN IF EXISTS cuota_numero');
        $this->addSql('DROP TABLE IF EXISTS expediente_escrito');
    }
}
