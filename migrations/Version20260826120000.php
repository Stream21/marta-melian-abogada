<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea expediente_documento_archivo (multi-archivo en requerimientos) y migra archivo_path legado.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS expediente_documento_archivo (
            id VARCHAR(36) NOT NULL,
            entregado_id VARCHAR(36) NOT NULL,
            archivo_path VARCHAR(500) NOT NULL,
            nombre_original VARCHAR(255) NOT NULL,
            orden INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_EXP_DOC_ARCHIVO_ENTREGADO ON expediente_documento_archivo (entregado_id)');
        $this->addSql("COMMENT ON COLUMN expediente_documento_archivo.created_at IS '(DC2Type:datetime_immutable)'");

        // Entregas antiguas con un solo archivo_path → una fila en la nueva tabla.
        $this->addSql("INSERT INTO expediente_documento_archivo (id, entregado_id, archivo_path, nombre_original, orden, created_at)
            SELECT
                md5(e.id || ':' || e.archivo_path),
                e.id,
                e.archivo_path,
                'documento.pdf',
                0,
                e.entregado_at
            FROM expediente_documento_entregado e
            WHERE e.archivo_path IS NOT NULL
              AND TRIM(e.archivo_path) <> ''
              AND NOT EXISTS (
                  SELECT 1 FROM expediente_documento_archivo a WHERE a.entregado_id = e.id
              )");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS expediente_documento_archivo');
    }
}
