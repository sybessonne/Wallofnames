<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Name;

use App\Service\Tools;
use App\Service\Mailer;

class Stripe
{
    private $em;
    private $secretKey;
    private $webhookKey;
    private $tools;
    private $mailer;

    public function __construct(EntityManagerInterface $entityManager, string $secretKey, string $webhookKey, Tools $tools, Mailer $mailer)
    {
        $this->em = $entityManager;
        $this->secretKey = $secretKey;
        $this->webhookKey = $webhookKey;
        $this->tools = $tools;
        $this->mailer = $mailer;
    }

    private function init()
    {
        \Stripe\Stripe::setApiKey($this->secretKey);
    }

    public function createOrder(Name $name, $stripePaymentMethod)
    {
        try {
            $this->init();

            $discountCodeString = ($name->getOrder()->getDiscountCode()) ? $name->getOrder()->getDiscountCode()->getCode() : "";
    
            $intent = \Stripe\PaymentIntent::create([
                'payment_method' => $stripePaymentMethod,
                'amount' => $name->getOrder()->getPrice() * 100,
                'currency' => $name->getOrder()->getCurrency(),
                'metadata' => ["id" => $name->getId()]
                ]);
                                                        
            $name->getOrder()->setPaymentId($intent->id);
            $this->em->flush();

            return array(false, $intent->client_secret);
        }
        catch(\Stripe\Exception\CardException $e) {}
        catch(\Stripe\Exception\RateLimitException $e) {} 
        catch(\Stripe\Exception\InvalidRequestException $e) {
            $this->mailer->sendMailErrorStripe($name, $e->getJsonBody()["error"]);    
        }
        catch(\Stripe\Exception\AuthenticationException $e) {} 
        catch(\Stripe\Exception\ApiConnectionException $e) {
            $this->mailer->sendMailErrorStripe($name, $e->getJsonBody()["error"]);    
        } 
        catch(\Stripe\Exception\ApiErrorException $e) {} 
        catch(\Exception $e) {}

        $this->tools->deleteName($name);
        return array(true, "");
    }

    private function getEvent($payload, $sig_header)
    {
        try {
            $this->init();
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $this->webhookKey
            );

            return array(false, $event);
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
        } catch(\Stripe\Error\SignatureVerification $e) {
            // Invalid signature
        }
        catch(\Stripe\Exception\CardException $e) {}
        catch (\Stripe\Exception\RateLimitException $e) {} 
        catch (\Stripe\Exception\InvalidRequestException $e) {} 
        catch (\Stripe\Exception\AuthenticationException $e) {} 
        catch (\Stripe\Exception\ApiConnectionException $e) {} 
        catch (\Stripe\Exception\ApiErrorException $e) {} 
        catch (\Exception $e) {}

        return array(true, null);
    }

    public function dispatchEvent($payload, $sig_header)
    {
        list($err, $event) = $this->getEvent($payload, $sig_header);

        //Si un problème au niveau de la récupération du payload
        if($err)
        {
            $outputCode = 400;
        }
        //On a réussit à décoder la trame envoyée par Stripe (c'est sûr que c'est Stripe)
        else
        {
            $paymentIntent = $event->data->object;           
            $event = $event->type;

            switch ($event) {
                case 'payment_intent.succeeded':
                    $name = $this->em->getRepository(Name::class)->findOneByPaymentId($paymentIntent->id);
                    if($this->tools->confirmName($name)) {
                        $this->mailer->sendConfirmPayment($name, $event, $paymentIntent);
                    }

                    $outputCode = 201;
                    break;
                case 'payment_intent.payment_failed':
                case 'payment_intent.canceled':
                    $name = $this->em->getRepository(Name::class)->findOneByPaymentId($paymentIntent->id);
                    if($this->tools->deleteName($name)){
                        $this->mailer->sendCancelPayment($name, $event, $paymentIntent);
                    }

                    $outputCode = 202;
                    break;
                case 'payment_intent.created':
                    $outputCode = 203;
                    break;
                case 'charge.refunded':
                    $name = $this->em->getRepository(Name::class)->findOneByPaymentId($paymentIntent->payment_intent);
                    if($this->tools->deleteName($name)){
                        $this->mailer->sendRefundPayment($name, $event, $paymentIntent);
                    }

                    $outputCode = 204;
                    break;
                case 'charge.dispute.updated':
                case 'charge.dispute.created':
                case 'radar.early_fraud_warning.updated':
                case 'radar.early_fraud_warning.created':
                case 'charge.dispute.funds_withdrawn':
                case 'charge.dispute.funds_reinstated':
                case 'charge.dispute.closed':
                    $this->mailer->sendAlertPayment("StripeCard", $event, $paymentIntent);
                    $outputCode = 205;
                    break;
                default:
                    $outputCode = 206;
            }
        }

        return $outputCode;
    }

    public function getPayment(string $paymentId)
    {
        $this->init();

        return \Stripe\PaymentIntent::retrieve($paymentId);
    }

    public function refund($paymentId)
    {
        try
        {
            $this->init();
            $refund = \Stripe\Refund::create([
                'payment_intent' => $paymentId
            ]);

            return $refund;
        }
        catch(\Stripe\Exception\CardException $e) {}
        catch (\Stripe\Exception\RateLimitException $e) {} 
        catch (\Stripe\Exception\InvalidRequestException $e) {} 
        catch (\Stripe\Exception\AuthenticationException $e) {} 
        catch (\Stripe\Exception\ApiConnectionException $e) {} 
        catch (\Stripe\Exception\ApiErrorException $e) {} 
        catch (\Exception $e) {}

        return null;
    }

    public function cancel($paymentId)
    {
        try{
            $payment_intent = $this->getPayment($paymentId);
            $payment_intent->cancel();
            return true;
        }
        catch(\Stripe\Exception\CardException $e) {}
        catch (\Stripe\Exception\RateLimitException $e) {} 
        catch (\Stripe\Exception\InvalidRequestException $e) {} 
        catch (\Stripe\Exception\AuthenticationException $e) {} 
        catch (\Stripe\Exception\ApiConnectionException $e) {} 
        catch (\Stripe\Exception\ApiErrorException $e) {} 
        catch (\Exception $e) {}
        
        return false;
    }
}