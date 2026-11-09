<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227163515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the project table and the Messenger transport table.';
    }

    /**
     * MySQL commits DDL implicitly, so there is no transaction left for
     * doctrine/migrations to commit. Without this it emits a "trying to commit
     * a transaction / already committed" deprecation on every run; the
     * `transactional: false` config key alone does not override this per-class
     * default.
     */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, title VARCHAR(128) DEFAULT NULL, tagline VARCHAR(255) DEFAULT NULL, summary LONGTEXT DEFAULT NULL, highlights JSON DEFAULT NULL, links JSON DEFAULT NULL, year VARCHAR(64) DEFAULT NULL, category VARCHAR(128) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
