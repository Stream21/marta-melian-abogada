<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260609140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tramite_documento_requerido table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE tramite_documento_requerido (
            id VARCHAR(36) NOT NULL,
            tramite_id VARCHAR(36) NOT NULL,
            fase VARCHAR(20) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            obligatorio BOOLEAN NOT NULL,
            formatos JSON NOT NULL,
            orden INT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_TRAMITE_DOC_REQ_TRAMITE ON tramite_documento_requerido (tramite_id)');
        $this->addSql('ALTER TABLE tramite_documento_requerido ADD CONSTRAINT FK_TRAMITE_DOC_REQ_TRAMITE FOREIGN KEY (tramite_id) REFERENCES tramite (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tramite_documento_requerido DROP CONSTRAINT FK_TRAMITE_DOC_REQ_TRAMITE');
        $this->addSql('DROP TABLE tramite_documento_requerido');
    }
}
