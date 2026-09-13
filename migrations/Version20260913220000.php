<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260913220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Requerimiento Mercurio: nombre y cometido del formulario entregable.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_requerimiento_mercurio ADD COLUMN IF NOT EXISTS formulario_nombre VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente_requerimiento_mercurio ADD COLUMN IF NOT EXISTS formulario_cometido TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_requerimiento_mercurio DROP COLUMN IF EXISTS formulario_nombre');
        $this->addSql('ALTER TABLE expediente_requerimiento_mercurio DROP COLUMN IF EXISTS formulario_cometido');
    }
}
