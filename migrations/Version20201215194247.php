<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201215194247 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $delays = [
            ['price' => 10, 'nb_days' => 1],
            ['price' => 18, 'nb_days' => 2],
            ['price' => 40, 'nb_days' => 7],
            ['price' => 120, 'nb_days' => 31],
            ['price' => 200, 'nb_days' => 62],
            ['price' => 400, 'nb_days' => 185],
        ];

        foreach ($delays as $delay) {
            $this->addSql('INSERT INTO delay (price, nb_days) VALUES (:price, :nb_days)', $delay);
        }

        $grades = [
            ['type' => "Normal", 'price' => 0],
        ];

        foreach ($grades as $grade) {
            $this->addSql('INSERT INTO grade (type, price) VALUES (:type, :price)', $grade);
        }

        $paymentMethods = [
            ['method' => "StripeCard"],
            ['method' => "PayPal"]
        ];

        foreach ($paymentMethods as $paymentMethod) {
            $this->addSql('INSERT INTO payment_method (method_name) VALUES (:method)', $paymentMethod);
        }

        $sizes = [
            ['size' => 8, 'price' => 5],
            ['size' => 10, 'price' => 10],
            ['size' => 14, 'price' => 15],
            ['size' => 18, 'price' => 20],
            ['size' => 22, 'price' => 25],
            ['size' => 26, 'price' => 30],
            ['size' => 30, 'price' => 35],
            ['size' => 34, 'price' => 40],
            ['size' => 38, 'price' => 45],
            ['size' => 42, 'price' => 50],
        ];

        foreach ($sizes as $size) {
            $this->addSql('INSERT INTO size (size, price) VALUES (:size, :price)', $size);
        }

        $textfonts = [
            ['tf'  => "Parisienne", 'p' => 4, 'f' => "parisienne", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Ruge Boogie", 'p' => 3, 'f' => "rugeBoogie", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Abril Fatface", 'p' => 2, 'f' => "abrilfatface", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Georgia", 'p' => 1, 'f' => "georgia", 'ex' => "ttf", "b" => 1, "i" => 1],
            ['tf'  => "Indie Flower", 'p' => 4, 'f' => "indieFlower", 'ex' => "ttf", "b" => 1, "i" => 1],
            ['tf'  => "Lucida Sans", 'p' => 1, 'f' => "LSANS",'ex' => "ttf", "b" => 1, "i" => 1],
            ['tf'  => "Palatino Linotype", 'p' => 1, 'f' => "pala", 'ex' => "ttf", "b" => 1, "i" => 1],
            ['tf'  => "Tahoma", 'p' => 1, 'f' => "tahoma", 'ex' => "ttf", "b" => 1, "i" => 1],
            ['tf'  => "Tangerine", 'p' => 4, 'f' => "tangerine", 'ex' => "ttf", "b" => 1, "i" => 0],
            ['tf'  => "Trebuchet MS", 'p' => 1, 'f' => "trebuc", 'ex' => "ttf", "b" => 1, "i" => 1],
            ['tf'  => "Verdana", 'p' => 1, 'f' => "verdana", 'ex' => "ttf", "b" => 1, "i" => 1],
            ['tf'  => "Aclonica", 'p' => 1, 'f' => "aclonica", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Aguafina Script", 'p' => 4, 'f' => "aguafinascript", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Akronim", 'p' => 3, 'f' => "akronim", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Aladin", 'p' => 1, 'f' => "aladin", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Allura", 'p' => 2, 'f' => "allura", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Bilbo", 'p' => 4, 'f' => "bilbo", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Codystar", 'p' => 4, 'f' => "codystar", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Miltonian", 'p' => 2, 'f' => "miltonian", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Fredericka the great", 'p' => 1, 'f' => "fredericka", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Good Night", 'p' => 4, 'f' => "goodNight", 'ex' => "ttf", "b" => 0, "i" => 0],
            ['tf'  => "Vinograd", 'p' => 5, 'f' => "vinograd", 'ex' => "ttf", "b" => 1, "i" => 1],
        ];

        foreach ($textfonts as $textfont) {
            $this->addSql('INSERT INTO text_font (text_font, price, text_font_file, extension, bold_available, italic_available) VALUES (:tf, :p, :f, :ex, :b, :i)', $textfont);
        }
    }

    public function down(Schema $schema) : void
    {
    }
}
