<?php

declare(strict_types=1);

namespace NaadConnector\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241004171841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase size of alerts.body column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE alerts MODIFY body MEDIUMTEXT NOT NULL;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE alerts MODIFY body TEXT NOT NULL;
        ');
    }
}
