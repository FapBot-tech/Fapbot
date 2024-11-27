<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240604083000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE page_content (id INT AUTO_INCREMENT NOT NULL, creator_id INT DEFAULT NULL, editor_id INT DEFAULT NULL, identifier VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, created DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4A5DB3C61220EA6 (creator_id), INDEX IDX_4A5DB3C6995AC4C (editor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE page_content ADD CONSTRAINT FK_4A5DB3C61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE page_content ADD CONSTRAINT FK_4A5DB3C6995AC4C FOREIGN KEY (editor_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE page_content DROP FOREIGN KEY FK_4A5DB3C61220EA6');
        $this->addSql('ALTER TABLE page_content DROP FOREIGN KEY FK_4A5DB3C6995AC4C');
        $this->addSql('DROP TABLE page_content');
    }
}
