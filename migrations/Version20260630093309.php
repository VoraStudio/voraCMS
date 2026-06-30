<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630093309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE content_types ADD COLUMN auto_clone BOOLEAN DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__content_types AS SELECT id, slug, name, description, active, base, created_at, user_id, project_id FROM content_types');
        $this->addSql('DROP TABLE content_types');
        $this->addSql('CREATE TABLE content_types (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, active BOOLEAN DEFAULT 1 NOT NULL, base BOOLEAN DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, project_id INTEGER DEFAULT NULL, CONSTRAINT FK_B2F3DDE2A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B2F3DDE2166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO content_types (id, slug, name, description, active, base, created_at, user_id, project_id) SELECT id, slug, name, description, active, base, created_at, user_id, project_id FROM __temp__content_types');
        $this->addSql('DROP TABLE __temp__content_types');
        $this->addSql('CREATE INDEX IDX_B2F3DDE2A76ED395 ON content_types (user_id)');
        $this->addSql('CREATE INDEX IDX_B2F3DDE2166D1F9C ON content_types (project_id)');
    }
}
