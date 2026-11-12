<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Gives skills a category and a proficiency level.
 *
 * Until now the admin skills editor stored only names, while the public page
 * rendered a hardcoded, grouped list -- so editing skills changed nothing a
 * visitor could see. Existing rows default to the backend/advanced pair the
 * old static list used.
 */
final class Version20261109210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add category and level to the skill table.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE skill ADD category VARCHAR(32) DEFAULT 'backend' NOT NULL");
        $this->addSql("ALTER TABLE skill ADD level VARCHAR(32) DEFAULT 'advanced' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill DROP category');
        $this->addSql('ALTER TABLE skill DROP level');
    }
}
