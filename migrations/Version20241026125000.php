<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241026125000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавить поле country в таблицу news_sources';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_sources ADD country VARCHAR(10) NOT NULL DEFAULT \'rus\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_sources DROP country');
    }
}
