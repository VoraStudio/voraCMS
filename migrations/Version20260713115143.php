<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260713115143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_request_log ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE api_request_log ADD CONSTRAINT FK_2862A307166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2862A307166D1F9C ON api_request_log (project_id)');
        $this->addSql('CREATE INDEX IDX_2862A307166D1F9C8B8E8428 ON api_request_log (project_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_request_log DROP FOREIGN KEY FK_2862A307166D1F9C');
        $this->addSql('DROP INDEX IDX_2862A307166D1F9C ON api_request_log');
        $this->addSql('DROP INDEX IDX_2862A307166D1F9C8B8E8428 ON api_request_log');
        $this->addSql('ALTER TABLE api_request_log DROP project_id');
    }
}
