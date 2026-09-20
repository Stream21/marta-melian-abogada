<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260919010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Requerimientos Mercurio: tipo documentación/tasas y oficio adjunto.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_requerimiento_mercurio ADD COLUMN IF NOT EXISTS oficio_path VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente_requerimiento_mercurio ADD COLUMN IF NOT EXISTS oficio_nombre VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE expediente_requerimiento_mercurio SET tipo = 'documentacion' WHERE tipo IN ('documento', 'escrito')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE expediente_requerimiento_mercurio SET tipo = 'documento' WHERE tipo IN ('documentacion', 'tasas')");
        $this->addSql('ALTER TABLE expediente_requerimiento_mercurio DROP oficio_nombre');
        $this->addSql('ALTER TABLE expediente_requerimiento_mercurio DROP oficio_path');
    }
}
