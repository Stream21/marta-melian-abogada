<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Nota de devolución del abogado en pasos de contratación';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_contratacion_paso ADD COLUMN IF NOT EXISTS nota_devolucion TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_contratacion_paso DROP COLUMN IF EXISTS nota_devolucion');
    }
}
