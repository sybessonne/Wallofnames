<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\HttpFoundation\File\UploadedFile;
/**
 * @ORM\Entity(repositoryClass="App\Repository\TextFontRepository")
 */
class TextFont
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     * @Assert\Length(
     *      min = 3,
     *      max = 40,
     *      minMessage = "Police trop courte, au moins {{ limit }} caractères",
     *      maxMessage = "Police trop longue, au plus {{ limit }} caractères",
     *      allowEmptyString = false
     * )
     */
    private $textFont;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $price;

    /**
     * @ORM\Column(type="text")
     * @Assert\Length(
     *      min = 3,
     *      max = 50,
     *      minMessage = "nom fichier Police trop courte, au moins {{ limit }} caractères",
     *      maxMessage = "nom fichier Police trop longue, au plus {{ limit }} caractères",
     *      allowEmptyString = false
     * )
     */
    private $textFontFile;

    /**
     * @ORM\Column(type="string", length=5)
     * @Assert\Length(
     *      min = 2,
     *      max = 5,
     *      minMessage = "Extension trop courte, au moins {{ limit }} caractères",
     *      maxMessage = "Extension trop longue, au plus {{ limit }} caractères",
     *      allowEmptyString = false
     * )
     */
    private $extension;

    /**
     * @ORM\Column(type="boolean")
     */
    private $boldAvailable;

    /**
     * @ORM\Column(type="boolean")
     */
    private $italicAvailable;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTextFont(): ?string
    {
        return $this->textFont;
    }

    public function setTextFont(string $textFont): self
    {
        $this->textFont = $textFont;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getBoldAvailable(): ?bool
    {
        return $this->boldAvailable;
    }

    public function setBoldAvailable(bool $boldAvailable): self
    {
        $this->boldAvailable = $boldAvailable;

        return $this;
    }

    public function getItalicAvailable(): ?bool
    {
        return $this->italicAvailable;
    }

    public function setItalicAvailable(bool $italicAvailable): self
    {
        $this->italicAvailable = $italicAvailable;

        return $this;
    }

    public function getTextFontFile(): ?string
    {
        return $this->textFontFile;
    }

    public function setTextFontFile(string $textFontFile): self
    {
        $this->textFontFile = $textFontFile;

        return $this;
    }

    //Method and property for the EasyAdmin Part
    private $fileBase;
    private $fileBold;
    private $fileItalic;
    private $fileBI;

    public function getFileBase(){ return $this->fileBase;}
    public function setFileBase(UploadedFile $f){ $this->fileBase = $f;}
    public function getFileBold(){ return $this->fileBold;}
    public function setFileBold(UploadedFile $f){ $this->fileBold = $f;}    
    public function getFileItalic(){ return $this->fileItalic;}
    public function setFileItalic(UploadedFile $f){ $this->fileItalic = $f;}    
    public function getFileBI(){ return $this->fileBI;}
    public function setFileBI(UploadedFile $f) {$this->fileBI = $f;}    
}
