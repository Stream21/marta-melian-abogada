<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tabla de gastos del bufete (CRUD + factura PDF opcional).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE gasto (
            id VARCHAR(36) NOT NULL,
            concepto VARCHAR(255) NOT NULL,
            importe VARCHAR(20) NOT NULL,
            fecha DATE NOT NULL,
            categoria VARCHAR(100) DEFAULT NULL,
            notas TEXT DEFAULT NULL,
            factura_pdf_path VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_gasto_fecha ON gasto (fecha)');
        $this->addSql('CREATE INDEX idx_gasto_categoria ON gasto (categoria)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE gasto');
    }
}
