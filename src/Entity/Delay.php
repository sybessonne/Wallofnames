<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DelayRepository")
 */
class Delay
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $price;

    /**
     * @ORM\Column(type="integer")
     * @Assert\PositiveOrZero
     */
    private $nbDays;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNbDays(): ?int
    {
        return $this->nbDays;
    }

    public function setNbDays(int $nbDays): self
    {
        $this->nbDays = $nbDays;

        return $this;
    }
}
