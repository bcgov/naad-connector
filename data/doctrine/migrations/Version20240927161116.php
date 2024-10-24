<?php

declare(strict_types=1);

namespace NaadConnector\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240927161116 extends AbstractMigration {

    public function getDescription(): string {
        return 'Create the alerts table';
    }

    public function up( Schema $schema ): void {
        $this->addSql(
            '
            CREATE TABLE alerts (
                id VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                received DATETIME NOT NULL,
                send_attempted DATETIME DEFAULT NULL,
                failures TINYINT DEFAULT 0,
                success BOOLEAN DEFAULT FALSE,
                PRIMARY KEY(id)
            )
        '
        );
    }

    public function down( Schema $schema ): void {
        $this->addSql(
            '
            DROP TABLE alerts;
        '
        );
    }
}
