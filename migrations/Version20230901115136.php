<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230901115136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mute_users (user_id INT NOT NULL, channel_id INT NOT NULL, INDEX IDX_A34A75D2A76ED395 (user_id), INDEX IDX_A34A75D272F5A1AA (channel_id), PRIMARY KEY(user_id, channel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mute_users ADD CONSTRAINT FK_A34A75D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mute_users ADD CONSTRAINT FK_A34A75D272F5A1AA FOREIGN KEY (channel_id) REFERENCES channel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mute_users DROP FOREIGN KEY FK_A34A75D2A76ED395');
        $this->addSql('ALTER TABLE mute_users DROP FOREIGN KEY FK_A34A75D272F5A1AA');
        $this->addSql('DROP TABLE mute_users');
    }
}
