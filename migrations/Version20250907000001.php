<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250907000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create currency_rates table for storing cryptocurrency exchange rates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE currency_rates (
            id INT AUTO_INCREMENT NOT NULL,
            pair VARCHAR(10) NOT NULL,
            rate DECIMAL(15,8) NOT NULL,
            timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            source VARCHAR(50) DEFAULT \'binance\',
            PRIMARY KEY(id, timestamp),
            INDEX idx_pair_timestamp (pair, timestamp),
            INDEX idx_timestamp (timestamp),
            UNIQUE KEY uk_pair_timestamp (pair, timestamp)
        ) ENGINE = InnoDB
        PARTITION BY RANGE (YEAR(timestamp) * 100 + MONTH(timestamp)) (
            PARTITION p_202501 VALUES LESS THAN (202501),
            PARTITION p_202502 VALUES LESS THAN (202502),
            PARTITION p_202503 VALUES LESS THAN (202503),
            PARTITION p_202504 VALUES LESS THAN (202504),
            PARTITION p_202505 VALUES LESS THAN (202505),
            PARTITION p_202506 VALUES LESS THAN (202506),
            PARTITION p_202507 VALUES LESS THAN (202507),
            PARTITION p_202508 VALUES LESS THAN (202508),
            PARTITION p_202509 VALUES LESS THAN (202509),
            PARTITION p_202510 VALUES LESS THAN (202510),
            PARTITION p_202511 VALUES LESS THAN (202511),
            PARTITION p_202512 VALUES LESS THAN (202512),
            PARTITION p_future VALUES LESS THAN MAXVALUE
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS currency_rates');
    }
}
