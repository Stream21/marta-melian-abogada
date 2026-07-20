<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\Persistence\Migration\ArraigoDocumentacionSeedData;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed documentación fase 2 para arraigos (2 docs servicio + 9 docs trámite)';
    }

    public function up(Schema $schema): void
    {
        $this->ensureSchema();

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $formatos = $this->escape(json_encode(['pdf'], JSON_THROW_ON_ERROR));

        foreach (ArraigoDocumentacionSeedData::documentosServicio() as $doc) {
            $this->insertServicioDocumento(ArraigoDocumentacionSeedData::SERVICIO_ARRAIGOS_ID, $doc, $formatos, $now);
        }

        foreach (ArraigoDocumentacionSeedData::documentosPorTramite() as $tramiteId => $documentos) {
            foreach ($documentos as $doc) {
                $this->insertTramiteDocumento($tramiteId, $doc, $formatos, $now);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $ids = [
            'd1000001-0001-4000-8000-000000000001',
            'd1000001-0001-4000-8000-000000000002',
            'd1000001-0001-4000-8000-000000000101',
            'd1000001-0001-4000-8000-000000000102',
            'd1000001-0001-4000-8000-000000000103',
            'd1000001-0001-4000-8000-000000000201',
            'd1000001-0001-4000-8000-000000000202',
            'd1000001-0001-4000-8000-000000000301',
            'd1000001-0001-4000-8000-000000000302',
            'd1000001-0001-4000-8000-000000000401',
            'd1000001-0001-4000-8000-000000000402',
        ];

        foreach ($ids as $id) {
            $this->addSql("DELETE FROM servicio_documento_requerido WHERE id = '{$id}'");
            $this->addSql("DELETE FROM tramite_documento_requerido WHERE id = '{$id}'");
        }
    }

    /**
     * @param array<string, mixed> $doc
     */
    private function insertServicioDocumento(string $servicioId, array $doc, string $formatos, string $now): void
    {
        $obligatorio = $doc['obligatorio'] ? 'true' : 'false';

        $this->addSql(<<<SQL
INSERT INTO servicio_documento_requerido (
    id, servicio_id, fase, nombre, descripcion, obligatorio, tipo, max_imagenes, formatos, orden, created_at, updated_at
)
SELECT
    '{$doc['id']}',
    '{$servicioId}',
    2,
    '{$this->escape($doc['nombre'])}',
    '{$this->escape($doc['descripcion'])}',
    {$obligatorio},
    '{$doc['tipo']}',
    {$doc['max_imagenes']},
    '{$formatos}'::json,
    {$doc['orden']},
    '{$now}',
    '{$now}'
WHERE NOT EXISTS (SELECT 1 FROM servicio_documento_requerido WHERE id = '{$doc['id']}')
SQL);
    }

    /**
     * @param array<string, mixed> $doc
     */
    private function insertTramiteDocumento(string $tramiteId, array $doc, string $formatos, string $now): void
    {
        $obligatorio = $doc['obligatorio'] ? 'true' : 'false';

        $this->addSql(<<<SQL
INSERT INTO tramite_documento_requerido (
    id, tramite_id, fase, nombre, descripcion, obligatorio, tipo, max_imagenes, formatos, orden, created_at, updated_at
)
SELECT
    '{$doc['id']}',
    '{$tramiteId}',
    2,
    '{$this->escape($doc['nombre'])}',
    '{$this->escape($doc['descripcion'])}',
    {$obligatorio},
    '{$doc['tipo']}',
    {$doc['max_imagenes']},
    '{$formatos}'::json,
    {$doc['orden']},
    '{$now}',
    '{$now}'
WHERE NOT EXISTS (SELECT 1 FROM tramite_documento_requerido WHERE id = '{$doc['id']}')
SQL);
    }

    private function escape(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    private function ensureSchema(): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS expediente_documento_requerido (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            obligatorio BOOLEAN NOT NULL,
            tipo VARCHAR(20) NOT NULL DEFAULT \'individual\',
            max_imagenes INT NOT NULL DEFAULT 1,
            orden INT NOT NULL DEFAULT 0,
            origen VARCHAR(20) NOT NULL DEFAULT \'tramite\',
            tramite_documento_requerido_id VARCHAR(36) DEFAULT NULL,
            servicio_documento_requerido_id VARCHAR(36) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_EXP_DOC_REQ_EXPEDIENTE ON expediente_documento_requerido (expediente_id)');

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

        $this->addSql('CREATE TABLE IF NOT EXISTS servicio_documento_requerido (
            id VARCHAR(36) NOT NULL,
            servicio_id VARCHAR(36) NOT NULL,
            fase INT NOT NULL DEFAULT 2,
            nombre VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            obligatorio BOOLEAN NOT NULL,
            tipo VARCHAR(20) NOT NULL DEFAULT \'individual\',
            max_imagenes INT NOT NULL DEFAULT 1,
            formatos JSON NOT NULL,
            orden INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_SERVICIO_DOC_REQ_SERVICIO ON servicio_documento_requerido (servicio_id)');

        $this->addSql(<<<'SQL'
DO $$ BEGIN
    ALTER TABLE servicio_documento_requerido
        ADD CONSTRAINT FK_SERVICIO_DOC_REQ_SERVICIO
        FOREIGN KEY (servicio_id) REFERENCES servicio (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
EXCEPTION
    WHEN duplicate_object THEN NULL;
END $$;
SQL);

        $this->addSql('ALTER TABLE expediente_documento_entregado ALTER COLUMN documento_requerido_id DROP NOT NULL');
        $this->addSql('ALTER TABLE expediente_documento_entregado ADD COLUMN IF NOT EXISTS expediente_documento_requerido_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE expediente_documento_entregado ADD COLUMN IF NOT EXISTS nota_rechazo TEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_EXP_DOC_ENTREGADO_EXP_REQ ON expediente_documento_entregado (expediente_id, expediente_documento_requerido_id) WHERE expediente_documento_requerido_id IS NOT NULL');
    }
}
