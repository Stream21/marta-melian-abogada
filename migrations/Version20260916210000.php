<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notas internas por expediente (tareas/recordatorios de despacho).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE expediente_nota (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            contenido TEXT NOT NULL,
            archivada BOOLEAN DEFAULT false NOT NULL,
            archivada_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_nota_expediente_created ON expediente_nota (expediente_id, created_at)');
        $this->addSql('ALTER TABLE expediente_nota ADD CONSTRAINT FK_NOTA_EXPEDIENTE FOREIGN KEY (expediente_id) REFERENCES expediente (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente_nota DROP CONSTRAINT FK_NOTA_EXPEDIENTE');
        $this->addSql('DROP TABLE expediente_nota');
    }
}
