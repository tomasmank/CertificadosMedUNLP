<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220204194916 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_attendee DROP FOREIGN KEY FK_57BC3CB771F7E88B');
        $this->addSql('ALTER TABLE event_attendee DROP FOREIGN KEY FK_57BC3CB7BCFD782A');
        $this->addSql('ALTER TABLE event_attendee ADD CONSTRAINT FK_57BC3CB771F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE event_attendee ADD CONSTRAINT FK_57BC3CB7BCFD782A FOREIGN KEY (attendee_id) REFERENCES attendee (id)');
        $this->addSql('ALTER TABLE template ADD img_id INT DEFAULT NULL, ADD background_color VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE template ADD CONSTRAINT FK_97601F83C06A9F55 FOREIGN KEY (img_id) REFERENCES image (id)');
        $this->addSql('CREATE INDEX IDX_97601F83C06A9F55 ON template (img_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE template DROP FOREIGN KEY FK_97601F83C06A9F55');
        $this->addSql('DROP TABLE image');
        $this->addSql('ALTER TABLE event_attendee DROP FOREIGN KEY FK_57BC3CB771F7E88B');
        $this->addSql('ALTER TABLE event_attendee DROP FOREIGN KEY FK_57BC3CB7BCFD782A');
        $this->addSql('ALTER TABLE event_attendee ADD CONSTRAINT FK_57BC3CB771F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_attendee ADD CONSTRAINT FK_57BC3CB7BCFD782A FOREIGN KEY (attendee_id) REFERENCES attendee (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP INDEX IDX_97601F83C06A9F55 ON template');
        $this->addSql('ALTER TABLE template DROP img_id, DROP background_color');
    }
}
