<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250228130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expediente columns (client_name, case_reference, folder_path, payment_status) and create payment table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expediente ADD client_name VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE expediente ADD case_reference VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE expediente ADD folder_path VARCHAR(500) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE expediente ADD payment_status VARCHAR(20) DEFAULT \'pending\' NOT NULL');

        $this->addSql('CREATE TABLE payment (
            id VARCHAR(36) NOT NULL,
            expediente_id VARCHAR(36) NOT NULL,
            status VARCHAR(20) NOT NULL,
            type VARCHAR(20) NOT NULL,
            holded_invoice_id VARCHAR(100) DEFAULT NULL,
            stripe_session_id VARCHAR(255) DEFAULT NULL,
            amount VARCHAR(20) NOT NULL,
            pdf_path VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE payment');
        $this->addSql('ALTER TABLE expediente DROP client_name');
        $this->addSql('ALTER TABLE expediente DROP case_reference');
        $this->addSql('ALTER TABLE expediente DROP folder_path');
        $this->addSql('ALTER TABLE expediente DROP payment_status');
    }
}
