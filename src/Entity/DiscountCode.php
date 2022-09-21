<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DiscountCodeRepository")
 */
class DiscountCode
{
    const VALID_REGEX = '/^[A-Z0-9]{5,15}$/';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20, options={"comment":"Entre 5 et 15 caractères [A-Z0-9]{5,15}"})
     * @Assert\NotBlank(message="promotion.incorrect")
     * @Assert\Regex(
     *     pattern=DiscountCode::VALID_REGEX,
     *     match=true,
     *     message="promotion.incorrect"
     *  )     
     */
    private $code;

    /**
     * @ORM\Column(type="date")
     * @Assert\GreaterThanOrEqual("today")
     */
    private $endDate;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $nbUses;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $discount;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $minPrice;

    public function __contruct()
    {
        $this->setCode("");
        $this->setEndDate(new \DateTime("today"));
        $this->setNbUses(0);
        $this->setDiscount(0);
        $this->setMinPrice(0);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getNbUses(): ?int
    {
        return $this->nbUses;
    }

    public function setNbUses(int $nbUses): self
    {
        $this->nbUses = $nbUses;

        return $this;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(int $discount): self
    {
        $this->discount = $discount;

        return $this;
    }

    public function getMinPrice(): ?int
    {
        return $this->minPrice;
    }

    public function setMinPrice(int $minPrice): self
    {
        $this->minPrice = $minPrice;

        return $this;
    }

}
