<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260601081521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE content_types (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, active BOOLEAN DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B2F3DDE2989D9B62 ON content_types (slug)');
        $this->addSql('CREATE TABLE entries (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, locale VARCHAR(5) DEFAULT \'ca\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, published_at DATE DEFAULT NULL, content_type_id INTEGER NOT NULL, author_id INTEGER DEFAULT NULL, CONSTRAINT FK_2DF8B3C51A445520 FOREIGN KEY (content_type_id) REFERENCES content_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2DF8B3C5F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C51A445520 ON entries (content_type_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5F675F31B ON entries (author_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C51A4455207B00651C ON entries (content_type_id, status)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C51A4455204180C698 ON entries (content_type_id, locale)');
        $this->addSql('CREATE TABLE field_definitions (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, field_type VARCHAR(50) NOT NULL, required BOOLEAN DEFAULT 0 NOT NULL, translatable BOOLEAN DEFAULT 1 NOT NULL, help_text CLOB DEFAULT NULL, sort_order INTEGER DEFAULT 0 NOT NULL, content_type_id INTEGER NOT NULL, CONSTRAINT FK_56D916151A445520 FOREIGN KEY (content_type_id) REFERENCES content_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_56D916151A445520 ON field_definitions (content_type_id)');
        $this->addSql('CREATE TABLE field_values (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, value CLOB DEFAULT NULL, entry_id INTEGER NOT NULL, field_definition_id INTEGER NOT NULL, CONSTRAINT FK_10E3C0E4BA364942 FOREIGN KEY (entry_id) REFERENCES entries (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_10E3C0E44D0FDD48 FOREIGN KEY (field_definition_id) REFERENCES field_definitions (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_10E3C0E4BA364942 ON field_values (entry_id)');
        $this->addSql('CREATE INDEX IDX_10E3C0E44D0FDD48 ON field_values (field_definition_id)');
        $this->addSql('CREATE TABLE media (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, extension VARCHAR(10) NOT NULL, mime_type VARCHAR(50) NOT NULL, path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, file_size INTEGER NOT NULL, alt_text CLOB DEFAULT NULL, created_at DATETIME NOT NULL, uploaded_by_id INTEGER DEFAULT NULL, CONSTRAINT FK_6A2CA10CA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6A2CA10CA2B28FE8 ON media (uploaded_by_id)');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, active BOOLEAN DEFAULT 1 NOT NULL, locale VARCHAR(255) DEFAULT \'ca\' NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE content_types');
        $this->addSql('DROP TABLE entries');
        $this->addSql('DROP TABLE field_definitions');
        $this->addSql('DROP TABLE field_values');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE users');
    }
}
