<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaymentMethodRepository")
 */
class PaymentMethod
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=15)
     * @Assert\Length(
     *      min = 2,
     *      max = 15,
     *      minMessage = "Méthode de paiement trop courte, au moins {{ limit }} caractères",
     *      maxMessage = "Méthode de paiement trop longue, au plus {{ limit }} caractères",
     *      allowEmptyString = false
     * )
     */
    private $methodName;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMethodName(): ?string
    {
        return $this->methodName;
    }

    public function setMethodName(string $methodName): self
    {
        $this->methodName = $methodName;

        return $this;
    }
}
