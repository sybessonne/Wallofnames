<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\DiscountCode;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="orders")
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 */
class Order
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     * @Assert\Type("string", message="addName.error")
     * @Assert\NotBlank(message="addName.buyerName")
     */
    private $buyerName;

    /**
     * @ORM\Column(type="text")
     * @Assert\Type("string", message="addName.error")
     * @Assert\NotBlank(message="addName.buyerEmail")
     * @Assert\Email(message="addName.buyerEmail")
     */
    private $buyerEmail;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Type("string", message="addName.error")
     * @Assert\Ip(message="addName.error")
     */
    private $ip;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\Type("string", message="addName.error")
     */
    private $orderNumber;

    /**
     * @ORM\Column(type="float")
     * @Assert\PositiveOrZero(message="addName.error")
     */
    private $price;

    /**
     * @ORM\Column(type="float")
     * @Assert\PositiveOrZero(message="addName.error")
     */
    private $priceBeforeDiscount;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\Type("string", message="addName.error")
     */
    private $currency;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $paid;

    /**
     * @ORM\Column(type="string", length=5)
     * @Assert\Type("string", message="addName.error")
     */
    private $language;

    /** 
     * @ORM\ManyToOne(targetEntity=PaymentMethod::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $paymentMethod;

    /** 
     * @ORM\ManyToOne(targetEntity=DiscountCode::class)
     */
    private $discountCode;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Type("string", message="addName.error")
     */
    private $receipt;

    /**
     * @ORM\Column(type="text", nullable=true, options={"comment":"StripeIntent ou paypalOrderID"})
     * @Assert\Type("string", message="addName.error")
     */
    private $paymentId;

    /**
     * @ORM\Column(type="text", nullable=true, options={"comment":"PayPal Capture ID"})
     * @Assert\Type("string", message="addName.error")
     */
    private $payPalCaptureId;

    /**
     * @ORM\Column(type="text", nullable=true, options={"comment":"PayPal Refund ID"})
     * @Assert\Type("string", message="addName.error")
     */
    private $payPalRefundId;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $refundReason;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $mailPayPalSent;

    public function __construct()
    {
        $this->paid = false;
        $this->discountCode = null;
        $this->linkReceipt = null;
        $this->paymentId = null;
        $this->payPalCaptureId = null;
        $this->payPalRefundId = null;
        $this->mailPayPalSent = false;
        $this->payPalCanceled = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBuyerName(): ?string
    {
        return $this->buyerName;
    }

    public function setBuyerName(string $buyerName): self
    {
        $this->buyerName = $buyerName;

        return $this;
    }

    public function getBuyerEmail(): ?string
    {
        return $this->buyerEmail;
    }

    public function setBuyerEmail(string $buyerEmail): self
    {
        $this->buyerEmail = $buyerEmail;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPriceBeforeDiscount(): ?float
    {
        return $this->priceBeforeDiscount;
    }

    public function setPriceBeforeDiscount(float $priceBeforeDiscount): self
    {
        $this->priceBeforeDiscount = $priceBeforeDiscount;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getPaid(): ?bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid): self
    {
        $this->paid = $paid;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getReceipt(): ?string
    {
        return $this->receipt;
    }

    public function setReceipt(?string $receipt): self
    {
        $this->receipt = $receipt;

        return $this;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(?string $paymentId): self
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(PaymentMethod $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getDiscountCode(): ?DiscountCode
    {
        return $this->discountCode;
    }

    public function setDiscountCode(?DiscountCode $discountCode): self
    {
        $this->discountCode = $discountCode;

        return $this;
    }

    public function getRefundReason(): ?string
    {
        return $this->refundReason;
    }

    public function setRefundReason(?string $refundReason): self
    {
        $this->refundReason = $refundReason;

        return $this;
    }

    public function getPayPalCaptureId(): ?string
    {
        return $this->payPalCaptureId;
    }

    public function setPayPalCaptureId(?string $payPalCaptureId): self
    {
        $this->payPalCaptureId = $payPalCaptureId;

        return $this;
    }

    public function getMailPayPalSent(): ?bool
    {
        return $this->mailPayPalSent;
    }

    public function setMailPayPalSent(bool $mailPayPalSent): self
    {
        $this->mailPayPalSent = $mailPayPalSent;

        return $this;
    }

    public function getPayPalRefundId(): ?string
    {
        return $this->payPalRefundId;
    }

    public function setPayPalRefundId(?string $payPalRefundId): self
    {
        $this->payPalRefundId = $payPalRefundId;

        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }
}
