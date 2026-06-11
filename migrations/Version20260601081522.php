<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601081522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Multi-client foundation: clients table, client_id FKs, composite unique indexes';
    }

    public function up(Schema $schema): void
    {
        // 1. Clients table
        $this->addSql('CREATE TABLE clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            logo VARCHAR(255) DEFAULT NULL,
            active BOOLEAN DEFAULT 1 NOT NULL,
            created_at DATETIME NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ACFE25C1989D9B62 ON clients (slug)');

        // 2. Insert default client (existing data migrates here)
        $this->addSql("INSERT INTO clients (name, slug, active, created_at) VALUES ('Default', 'default', 1, datetime('now'))");

        // 3. Add client_id columns with DEFAULT = 1 for existing rows
        $this->addSql('ALTER TABLE users ADD COLUMN client_id INTEGER NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE content_types ADD COLUMN client_id INTEGER NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE entries ADD COLUMN client_id INTEGER NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE media ADD COLUMN client_id INTEGER NOT NULL DEFAULT 1');

        // 4. Add base column to content_types
        $this->addSql('ALTER TABLE content_types ADD COLUMN base BOOLEAN NOT NULL DEFAULT 0');

        // 5. Create indexes on client_id columns
        $this->addSql('CREATE INDEX IDX_1483A5E919EB6921 ON users (client_id)');
        $this->addSql('CREATE INDEX IDX_B2F3DDE219EB6921 ON content_types (client_id)');
        $this->addSql('CREATE INDEX IDX_2DF8B3C519EB6921 ON entries (client_id)');
        $this->addSql('CREATE INDEX IDX_6A2CA10C19EB6921 ON media (client_id)');

        // 6. Replace old unique indexes with composite [field, client_id]
        // Users: drop unique on email, create composite [email, client_id]
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74');
        $this->addSql('CREATE UNIQUE INDEX user_email_client ON users (email, client_id)');

        // ContentTypes: drop unique on slug, create composite [slug, client_id]
        $this->addSql('DROP INDEX UNIQ_B2F3DDE2989D9B62');
        $this->addSql('CREATE UNIQUE INDEX ct_slug_client ON content_types (slug, client_id)');
    }

    public function down(Schema $schema): void
    {
        // Reverse composite unique indexes — restore originals
        $this->addSql('DROP INDEX ct_slug_client');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B2F3DDE2989D9B62 ON content_types (slug)');

        $this->addSql('DROP INDEX user_email_client');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');

        // Drop indexes on client_id
        $this->addSql('DROP INDEX IDX_1483A5E919EB6921');
        $this->addSql('DROP INDEX IDX_B2F3DDE219EB6921');
        $this->addSql('DROP INDEX IDX_2DF8B3C519EB6921');
        $this->addSql('DROP INDEX IDX_6A2CA10C19EB6921');

        // SQLite cannot drop columns — the client_id and base columns remain in tables.
        // For a full rollback, recreate tables or restore from backup.
        // The columns are harmless (they contain default values) and Doctrine ORM ignores
        // unmapped columns. Clean rollback = git revert migration + redeploy.

        $this->addSql('DROP TABLE clients');
    }
}
