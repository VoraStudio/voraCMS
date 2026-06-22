<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260612110922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE projects (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, active BOOLEAN DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, client_id INTEGER NOT NULL, CONSTRAINT FK_5C93B3A419EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5C93B3A419EB6921 ON projects (client_id)');
        $this->addSql('CREATE UNIQUE INDEX project_slug_client ON projects (slug, client_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__clients AS SELECT id, name, slug, logo, active, created_at FROM clients');
        $this->addSql('DROP TABLE clients');
        $this->addSql('CREATE TABLE clients (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, logo VARCHAR(255) DEFAULT NULL, active BOOLEAN DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO clients (id, name, slug, logo, active, created_at) SELECT id, name, slug, logo, active, created_at FROM __temp__clients');
        $this->addSql('DROP TABLE __temp__clients');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C82E74989D9B62 ON clients (slug)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__content_types AS SELECT id, slug, name, description, active, created_at, client_id, base FROM content_types');
        $this->addSql('DROP TABLE content_types');
        $this->addSql('CREATE TABLE content_types (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, active BOOLEAN DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, client_id INTEGER NOT NULL, base BOOLEAN DEFAULT 0 NOT NULL, project_id INTEGER DEFAULT NULL, CONSTRAINT FK_B2F3DDE219EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B2F3DDE2166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO content_types (id, slug, name, description, active, created_at, client_id, base) SELECT id, slug, name, description, active, created_at, client_id, base FROM __temp__content_types');
        $this->addSql('DROP TABLE __temp__content_types');
        $this->addSql('CREATE INDEX IDX_B2F3DDE219EB6921 ON content_types (client_id)');
        $this->addSql('CREATE INDEX IDX_B2F3DDE2166D1F9C ON content_types (project_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__entries AS SELECT id, status, locale, created_at, updated_at, published_at, content_type_id, author_id, client_id FROM entries');
        $this->addSql('DROP TABLE entries');
        $this->addSql('CREATE TABLE entries (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, locale VARCHAR(5) DEFAULT \'ca\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, published_at DATE DEFAULT NULL, content_type_id INTEGER NOT NULL, author_id INTEGER DEFAULT NULL, client_id INTEGER NOT NULL, active BOOLEAN DEFAULT 1 NOT NULL, CONSTRAINT FK_2DF8B3C51A445520 FOREIGN KEY (content_type_id) REFERENCES content_types (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2DF8B3C5F675F31B FOREIGN KEY (author_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2DF8B3C519EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO entries (id, status, locale, created_at, updated_at, published_at, content_type_id, author_id, client_id) SELECT id, status, locale, created_at, updated_at, published_at, content_type_id, author_id, client_id FROM __temp__entries');
        $this->addSql('DROP TABLE __temp__entries');
        $this->addSql('CREATE INDEX IDX_2DF8B3C519EB6921 ON entries (client_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C51A4455204180C698 ON entries (content_type_id, locale)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C51A4455207B00651C ON entries (content_type_id, status)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5F675F31B ON entries (author_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C51A445520 ON entries (content_type_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__media AS SELECT id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, client_id FROM media');
        $this->addSql('DROP TABLE media');
        $this->addSql('CREATE TABLE media (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, extension VARCHAR(10) NOT NULL, mime_type VARCHAR(50) NOT NULL, path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, file_size INTEGER NOT NULL, alt_text CLOB DEFAULT NULL, created_at DATETIME NOT NULL, uploaded_by_id INTEGER DEFAULT NULL, client_id INTEGER NOT NULL, CONSTRAINT FK_6A2CA10CA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6A2CA10C19EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO media (id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, client_id) SELECT id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, client_id FROM __temp__media');
        $this->addSql('DROP TABLE __temp__media');
        $this->addSql('CREATE INDEX IDX_6A2CA10C19EB6921 ON media (client_id)');
        $this->addSql('CREATE INDEX IDX_6A2CA10CA2B28FE8 ON media (uploaded_by_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, name, roles, password, active, locale, created_at, client_id FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, active BOOLEAN DEFAULT 1 NOT NULL, locale VARCHAR(255) DEFAULT \'ca\' NOT NULL, created_at DATETIME NOT NULL, client_id INTEGER NOT NULL, CONSTRAINT FK_1483A5E919EB6921 FOREIGN KEY (client_id) REFERENCES clients (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO users (id, email, name, roles, password, active, locale, created_at, client_id) SELECT id, email, name, roles, password, active, locale, created_at, client_id FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE INDEX IDX_1483A5E919EB6921 ON users (client_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE projects');
        $this->addSql('CREATE TEMPORARY TABLE __temp__clients AS SELECT id, name, slug, logo, active, created_at FROM clients');
        $this->addSql('DROP TABLE clients');
        $this->addSql('CREATE TABLE clients (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(100) NOT NULL, logo VARCHAR(255) DEFAULT NULL, active BOOLEAN DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO clients (id, name, slug, logo, active, created_at) SELECT id, name, slug, logo, active, created_at FROM __temp__clients');
        $this->addSql('DROP TABLE __temp__clients');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ACFE25C1989D9B62 ON clients (slug)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__content_types AS SELECT id, slug, name, description, active, base, created_at, client_id FROM content_types');
        $this->addSql('DROP TABLE content_types');
        $this->addSql('CREATE TABLE content_types (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, slug VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, active BOOLEAN DEFAULT 1 NOT NULL, base BOOLEAN DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, client_id INTEGER DEFAULT 1 NOT NULL)');
        $this->addSql('INSERT INTO content_types (id, slug, name, description, active, base, created_at, client_id) SELECT id, slug, name, description, active, base, created_at, client_id FROM __temp__content_types');
        $this->addSql('DROP TABLE __temp__content_types');
        $this->addSql('CREATE INDEX IDX_B2F3DDE219EB6921 ON content_types (client_id)');
        $this->addSql('CREATE UNIQUE INDEX ct_slug_client ON content_types (slug, client_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__entries AS SELECT id, status, locale, created_at, updated_at, published_at, content_type_id, author_id, client_id FROM entries');
        $this->addSql('DROP TABLE entries');
        $this->addSql('CREATE TABLE entries (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status VARCHAR(20) DEFAULT \'draft\' NOT NULL, locale VARCHAR(5) DEFAULT \'ca\' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, published_at DATE DEFAULT NULL, content_type_id INTEGER NOT NULL, author_id INTEGER DEFAULT NULL, client_id INTEGER DEFAULT 1 NOT NULL, CONSTRAINT FK_2DF8B3C51A445520 FOREIGN KEY (content_type_id) REFERENCES content_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_2DF8B3C5F675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO entries (id, status, locale, created_at, updated_at, published_at, content_type_id, author_id, client_id) SELECT id, status, locale, created_at, updated_at, published_at, content_type_id, author_id, client_id FROM __temp__entries');
        $this->addSql('DROP TABLE __temp__entries');
        $this->addSql('CREATE INDEX IDX_2DF8B3C51A445520 ON entries (content_type_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C5F675F31B ON entries (author_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C519EB6921 ON entries (client_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C51A4455207B00651C ON entries (content_type_id, status)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C51A4455204180C698 ON entries (content_type_id, locale)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__media AS SELECT id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, client_id FROM media');
        $this->addSql('DROP TABLE media');
        $this->addSql('CREATE TABLE media (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_filename VARCHAR(255) NOT NULL, extension VARCHAR(10) NOT NULL, mime_type VARCHAR(50) NOT NULL, path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, file_size INTEGER NOT NULL, alt_text CLOB DEFAULT NULL, created_at DATETIME NOT NULL, uploaded_by_id INTEGER DEFAULT NULL, client_id INTEGER DEFAULT 1 NOT NULL, CONSTRAINT FK_6A2CA10CA2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO media (id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, client_id) SELECT id, filename, original_filename, extension, mime_type, path, thumbnail_path, file_size, alt_text, created_at, uploaded_by_id, client_id FROM __temp__media');
        $this->addSql('DROP TABLE __temp__media');
        $this->addSql('CREATE INDEX IDX_6A2CA10CA2B28FE8 ON media (uploaded_by_id)');
        $this->addSql('CREATE INDEX IDX_6A2CA10C19EB6921 ON media (client_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, name, roles, password, active, locale, created_at, client_id FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, active BOOLEAN DEFAULT 1 NOT NULL, locale VARCHAR(255) DEFAULT \'ca\' NOT NULL, created_at DATETIME NOT NULL, client_id INTEGER DEFAULT 1 NOT NULL)');
        $this->addSql('INSERT INTO users (id, email, name, roles, password, active, locale, created_at, client_id) SELECT id, email, name, roles, password, active, locale, created_at, client_id FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE INDEX IDX_1483A5E919EB6921 ON users (client_id)');
        $this->addSql('CREATE UNIQUE INDEX user_email_client ON users (email, client_id)');
    }
}
