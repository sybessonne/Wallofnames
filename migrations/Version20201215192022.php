<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201215192022 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, email VARCHAR(50) NOT NULL, subject LONGTEXT NOT NULL, message LONGTEXT NOT NULL, contact_date DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE delay (id INT AUTO_INCREMENT NOT NULL, price INT NOT NULL, nb_days INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE discount_code (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL COMMENT \'Entre 5 et 15 caractères [A-Z0-9]{5,15}\', end_date DATE NOT NULL, nb_uses INT NOT NULL, discount INT NOT NULL, min_price INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE grade (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, price INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE name (id INT AUTO_INCREMENT NOT NULL, size_id INT NOT NULL, text_font_id INT NOT NULL, delay_id INT NOT NULL, grade_id INT NOT NULL, order_id INT NOT NULL, name VARCHAR(30) NOT NULL, added_date DATETIME NOT NULL, color VARCHAR(7) NOT NULL, position_x INT NOT NULL, position_y INT NOT NULL, width INT NOT NULL, height INT NOT NULL, bold TINYINT(1) NOT NULL, italic TINYINT(1) NOT NULL, confirmation TINYINT(1) DEFAULT \'0\' NOT NULL, validation TINYINT(1) DEFAULT \'0\' NOT NULL, confirmation_date DATETIME DEFAULT NULL, deletion TINYINT(1) DEFAULT \'0\' NOT NULL, deletion_date DATETIME DEFAULT NULL, secret_key LONGTEXT NOT NULL, url_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, INDEX IDX_5E237E06498DA827 (size_id), INDEX IDX_5E237E06F356D6D4 (text_font_id), INDEX IDX_5E237E06BC5E7082 (delay_id), INDEX IDX_5E237E06FE19A1A8 (grade_id), UNIQUE INDEX UNIQ_5E237E068D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, payment_method_id INT NOT NULL, discount_code_id INT DEFAULT NULL, buyer_name LONGTEXT NOT NULL, buyer_email LONGTEXT NOT NULL, ip LONGTEXT DEFAULT NULL, order_number VARCHAR(10) NOT NULL, price DOUBLE PRECISION NOT NULL, price_before_discount DOUBLE PRECISION NOT NULL, currency VARCHAR(10) NOT NULL, paid TINYINT(1) DEFAULT \'0\' NOT NULL, language VARCHAR(5) NOT NULL, receipt LONGTEXT DEFAULT NULL, payment_id LONGTEXT DEFAULT NULL COMMENT \'StripeIntent ou paypalOrderID\', pay_pal_capture_id LONGTEXT DEFAULT NULL COMMENT \'PayPal Capture ID\', pay_pal_refund_id LONGTEXT DEFAULT NULL COMMENT \'PayPal Refund ID\', refund_reason VARCHAR(10) DEFAULT NULL, mail_pay_pal_sent TINYINT(1) DEFAULT \'0\' NOT NULL, INDEX IDX_E52FFDEE5AA1164F (payment_method_id), INDEX IDX_E52FFDEE91D29306 (discount_code_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_method (id INT AUTO_INCREMENT NOT NULL, method_name VARCHAR(15) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE size (id INT AUTO_INCREMENT NOT NULL, size INT NOT NULL, price INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE text_font (id INT AUTO_INCREMENT NOT NULL, text_font LONGTEXT NOT NULL, price INT NOT NULL, text_font_file LONGTEXT NOT NULL, extension VARCHAR(5) NOT NULL, bold_available TINYINT(1) NOT NULL, italic_available TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE name ADD CONSTRAINT FK_5E237E06498DA827 FOREIGN KEY (size_id) REFERENCES size (id)');
        $this->addSql('ALTER TABLE name ADD CONSTRAINT FK_5E237E06F356D6D4 FOREIGN KEY (text_font_id) REFERENCES text_font (id)');
        $this->addSql('ALTER TABLE name ADD CONSTRAINT FK_5E237E06BC5E7082 FOREIGN KEY (delay_id) REFERENCES delay (id)');
        $this->addSql('ALTER TABLE name ADD CONSTRAINT FK_5E237E06FE19A1A8 FOREIGN KEY (grade_id) REFERENCES grade (id)');
        $this->addSql('ALTER TABLE name ADD CONSTRAINT FK_5E237E068D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE5AA1164F FOREIGN KEY (payment_method_id) REFERENCES payment_method (id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE91D29306 FOREIGN KEY (discount_code_id) REFERENCES discount_code (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE name DROP FOREIGN KEY FK_5E237E06BC5E7082');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE91D29306');
        $this->addSql('ALTER TABLE name DROP FOREIGN KEY FK_5E237E06FE19A1A8');
        $this->addSql('ALTER TABLE name DROP FOREIGN KEY FK_5E237E068D9F6D38');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE5AA1164F');
        $this->addSql('ALTER TABLE name DROP FOREIGN KEY FK_5E237E06498DA827');
        $this->addSql('ALTER TABLE name DROP FOREIGN KEY FK_5E237E06F356D6D4');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE delay');
        $this->addSql('DROP TABLE discount_code');
        $this->addSql('DROP TABLE grade');
        $this->addSql('DROP TABLE name');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP TABLE size');
        $this->addSql('DROP TABLE text_font');
        $this->addSql('DROP TABLE user');
    }
}
