<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NameRepository")
 */
class Name
{
    const VALID_REGEX_NAME = '/^[a-zA-Z0-9_.]{1,30}$/u'; //ATTENTION : Si on change le nom de caractères, changer aussi dans services.yaml
    const VALID_REGEX_COLOR = '/^#(?:(?:[[:xdigit:]]{3}){1,2})$/i';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=30)
     * @Assert\NotBlank(message="addName.nameEmpty")
     * @Assert\Type("string", message="addName.error")
     * @Assert\Regex(
     *     pattern=Name::VALID_REGEX_NAME,
     *     match=true,
     *     message="addName.name"
     *  )  
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $addedDate;

    /**
     * @ORM\Column(type="string", length=7)
     * @Assert\NotBlank(message="addName.colorEmpty")
     * @Assert\Type("string", message="addName.error")
     * @Assert\Regex(
     *     pattern=Name::VALID_REGEX_COLOR,
     *     match=true,
     *     message="addName.color"
     *  )  
     */
    private $color;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero(message="addName.error")
     */
    private $positionX;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero(message="addName.error")
     */
    private $positionY;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero(message="addName.error")
     */
    private $width;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero(message="addName.error")
     */
    private $height;

    /**
     * @ORM\Column(type="boolean")
     */
    private $bold;

    /**
     * @ORM\Column(type="boolean")
     */
    private $italic;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $confirmation;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $validation;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $confirmationDate;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $deletion;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletionDate;

    /** 
     * @ORM\ManyToOne(targetEntity=Size::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $size;

    /** 
     * @ORM\ManyToOne(targetEntity=TextFont::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $textFont;

    /** 
     * @ORM\ManyToOne(targetEntity=Delay::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $delay;

    /** 
     * @ORM\ManyToOne(targetEntity=Grade::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $grade;

    /**
     * @ORM\Column(type="text")
     * @Assert\Type("string", message="addName.error")
     */
    private $secretKey;

    /** 
     * @ORM\OneToOne(targetEntity=Order::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $order;

    /**
     * @ORM\Column(type="boolean", options={"default": 1})
     */
    private $urlEnabled;

    public function __construct(string $name, string $color, bool $bold, bool $italic, Size $size,
                                TextFont $textFont, Delay $delay, Grade $grade, int $positionX = 0, int $positionY = 0,
                                int $width = 0, int $height = 0)
    {
        $this->setName($name);
        $this->setColor($color);
        $this->setPositionX($positionX);
        $this->setPositionY($positionY);
        $this->setWidth($width);
        $this->setHeight($height);
        $this->setBold($bold);
        $this->setItalic($italic);
        $this->setConfirmation(false);
        $this->setValidation(false);
        $this->setConfirmationDate(null);
        $this->setDeletion(false);
        $this->setDeletionDate(null);
        $this->setSize($size);
        $this->setTextFont($textFont);
        $this->setDelay($delay);
        $this->setGrade($grade);
        $this->setSecretKey("");
        $this->setAddedDate(new \DateTime());
        $this->setUrlEnabled(true);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAddedDate(): ?\DateTimeInterface
    {
        return $this->addedDate;
    }

    public function setAddedDate(\DateTimeInterface $addedDate): self
    {
        $this->addedDate = $addedDate;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getPositionX(): ?int
    {
        return $this->positionX;
    }

    public function setPositionX(int $positionX): self
    {
        $this->positionX = $positionX;

        return $this;
    }

    public function getPositionY(): ?int
    {
        return $this->positionY;
    }

    public function setPositionY(int $positionY): self
    {
        $this->positionY = $positionY;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getBold(): ?bool
    {
        return $this->bold;
    }

    public function setBold(bool $bold): self
    {
        $this->bold = $bold;

        return $this;
    }

    public function getItalic(): ?bool
    {
        return $this->italic;
    }

    public function setItalic(bool $italic): self
    {
        $this->italic = $italic;

        return $this;
    }

    public function getConfirmation(): ?bool
    {
        return $this->confirmation;
    }

    public function setConfirmation(bool $confirmation): self
    {
        $this->confirmation = $confirmation;

        return $this;
    }

    public function getValidation(): ?bool
    {
        return $this->validation;
    }

    public function setValidation(bool $validation): self
    {
        $this->validation = $validation;

        return $this;
    }

    public function getConfirmationDate(): ?\DateTimeInterface
    {
        return $this->confirmationDate;
    }

    public function setConfirmationDate(?\DateTimeInterface $confirmationDate): self
    {
        $this->confirmationDate = $confirmationDate;

        return $this;
    }

    public function getDeletion(): ?bool
    {
        return $this->deletion;
    }

    public function setDeletion(bool $deletion): self
    {
        $this->deletion = $deletion;

        return $this;
    }

    public function getDeletionDate(): ?\DateTimeInterface
    {
        return $this->deletionDate;
    }

    public function setDeletionDate(?\DateTimeInterface $deletionDate): self
    {
        $this->deletionDate = $deletionDate;

        return $this;
    }

    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    public function setSecretKey(string $secretKey): self
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function getSize(): ?Size
    {
        return $this->size;
    }

    public function setSize(Size $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getTextFont(): ?TextFont
    {
        return $this->textFont;
    }

    public function setTextFont(TextFont $textFont): self
    {
        $this->textFont = $textFont;

        return $this;
    }

    public function getDelay(): ?Delay
    {
        return $this->delay;
    }

    public function setDelay(Delay $delay): self
    {
        $this->delay = $delay;

        return $this;
    }

    public function getGrade(): ?Grade
    {
        return $this->grade;
    }

    public function setGrade(Grade $grade): self
    {
        $this->grade = $grade;

        return $this;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function idForMail()
    {
        return "(".$this->getId().")";
    }

    public function toEmail()
    {
        return "<br><br>ID:".$this->getId()."<br>Name:".$this->getName()."<br> color:".$this->getColor().
                "<br> size:".$this->getSize()->getSize()."<br> textFont:".$this->getTextFont()->getTextFont().
                "<br> positionX:".$this->getPositionX()."<br> positionY:".$this->getPositionY().
                "<br> width:".$this->getWidth()."<br>height:".$this->getHeight().
                "<br> bold:".intval($this->getBold())."<br> italic:".intval($this->getItalic()).
                "<br> delay:".$this->getDelay()->getNbDays()."<br> grade:".$this->getGrade()->getType().
                "<br>add Date:".$this->getAddedDate()->format('d/m/Y H:i:s').
                "<br><br>Order : ".$this->getOrder()->getId().
                "<br> price:".$this->getOrder()->getPrice()."<br> buyerName:".$this->getOrder()->getBuyerName().
                "<br> buyerEmail:".$this->getOrder()->getBuyerEmail().
                "<br> currency: ".$this->getOrder()->getCurrency().
                "<br> method payment: ".$this->getOrder()->getPaymentMethod()->getMethodName();
    }

    public function getUrlEnabled(): ?bool
    {
        return $this->urlEnabled;
    }

    public function setUrlEnabled(bool $urlEnabled): self
    {
        $this->urlEnabled = $urlEnabled;

        return $this;
    }
}
