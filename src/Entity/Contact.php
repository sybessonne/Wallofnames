<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ContactRepository")
 */
class Contact
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank(message="contact.name.not_blank")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank(message="contact.email.not_blank")
     * @Assert\Email(message="contact.email.incorrect")
     */
    private $email;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="contact.subject.not_blank")
     */
    private $subject;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="contact.message.not_blank")
     */
    private $message;

    /**
     * @ORM\Column(type="datetime")
     */
    private $contactDate;

    public function __construct()
    {
        $this->setContactDate(new \DateTime());
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getContactDate(): ?\DateTimeInterface
    {
        return $this->contactDate;
    }

    public function setContactDate(\DateTimeInterface $contactDate): self
    {
        $this->contactDate = $contactDate;

        return $this;
    }

    public function toEmail()
    {
        return "name : ".$this->getName()."<br>email : ".$this->getEmail()."<br>subject : ".$this->getSubject()."<br>
                message : ".$this->getMessage();
    }
}
