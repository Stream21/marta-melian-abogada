<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create invoice table for Holded billing module';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE invoice (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) DEFAULT NULL,
            holded_id VARCHAR(24) NOT NULL,
            numero VARCHAR(50) NOT NULL,
            concepto VARCHAR(500) NOT NULL,
            modalidad VARCHAR(100) NOT NULL,
            fecha TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            importe DOUBLE PRECISION NOT NULL,
            estado_holded VARCHAR(50) NOT NULL,
            pdf_path VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE invoice');
    }
}
