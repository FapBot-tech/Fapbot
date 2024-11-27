<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211218185654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mute ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mute ADD CONSTRAINT FK_CCB9735CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CCB9735CA76ED395 ON mute (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mute DROP FOREIGN KEY FK_CCB9735CA76ED395');
        $this->addSql('DROP INDEX UNIQ_CCB9735CA76ED395 ON mute');
        $this->addSql('ALTER TABLE mute DROP user_id');
    }
}
