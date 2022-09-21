<?php

namespace App\Service;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\VerifyWebhookSignature;
use PayPal\Api\WebhookEvent;
use PayPal\Rest\ApiContext;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Name;

ini_set('error_reporting', E_ALL); // or error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

class PayPal
{
    private $em;
    private $clientId;
    private $clientSecret;
    private $webhookId;
    private $tools;
    private $mailer;

    public function __construct(EntityManagerInterface $entityManager, string $clientId, string $clientSecret, string $webhookId, Tools $tools, Mailer $mailer)
    {
        $this->em = $entityManager;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->webhookId = $webhookId;
        $this->tools = $tools;
        $this->mailer = $mailer;
    }

    private function getApiContext()
    {
        return new ApiContext(
            new OAuthTokenCredential(
                $this->clientId,
                $this->clientSecret
            )
        );
    }
    
    private function client()
    {
        return new PayPalHttpClient(new SandboxEnvironment($this->clientId, $this->clientSecret));
    }

    public function createOrder(Name $name)
    {
        try{
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = $this->buildRequestBody($name);
            $client = $this->client();
            $response = $client->execute($request);

            if($response->result->status === "CREATED")
            {
                $name->getOrder()->setPaymentId($response->result->id);
                $this->em->flush();
        
                return array(false, $response->result->id);
            }

            $this->mailer->sendMailErrorPayPal($name, $response->result);
        }
        catch (\PayPalHttp\HttpException $ex) {
            $this->mailer->sendMailErrorPayPal($name, $ex->getMessage());
        }

        $this->tools->deleteName($name);
        return array(true, "");
    }
        
    /**
     * Setting up the JSON request body for creating the order with minimum request body. The intent in the
     * request body should be "AUTHORIZE" for authorize intent flow.
     *
     */
    private function buildRequestBody($name)
    {
        return array(
            'intent' => 'CAPTURE',
           /* 'application_context' =>
                array(
                    'return_url' => 'https://preprod.wallofnames.fr/test',
                    'cancel_url' => 'https://preprod.wallofnames.fr/test'
                ),*/
            'purchase_units' =>
                array(
                    0 =>
                        array(
                            'amount' =>
                                array(
                                    'currency_code' => $name->getOrder()->getCurrency(),
                                    'value' => strval($name->getOrder()->getPrice())
                                )
                        )
                )
        );
    }

    public function capturePayment(string $orderId)
    {
        try{
            $request = new OrdersCaptureRequest($orderId);
            //$request->body = self::buildRequestBody();
            $client = $this->client();
            $response = $client->execute($request);

            return $response;
        }
        catch (\PayPalHttp\HttpException $ex) {} 
        catch (\Exception $ex) {}

        return null;
    }

    private function getEvent($body, $headers)
    {
        $signatureVerification = new VerifyWebhookSignature();

        $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO'][0]);
        $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID'][0]);
        $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL'][0]);
        $signatureVerification->setWebhookId($this->webhookId); 
        $signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG'][0]);
        $signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME'][0]);
        
        $signatureVerification->setRequestBody($body);
        $request = clone $signatureVerification;
        
        try {
            $output = $signatureVerification->post($this->getApiContext());

            if($output->getVerificationStatus() === "SUCCESS"){
                return array(false, $request->toJSON());
            }
        }
        catch (\PayPalHttp\HttpException $ex) {} 
        catch (\Exception $ex) {}

        return array(true, null);
    }

    public function dispatchEvent($body, $headers)
    {
        list($err, $response) = $this->getEvent($body, $headers);

        //Si un problème au niveau de la récupération du payload
        if($err)
        {
            $outputCode = 200;
        }
        //On a réussit à décoder la trame envoyée par PayPal (c'est sûr que c'est PayPal)
        else
        {
            $res = json_decode($response);

            $resource = $res->webhook_event->resource;           
            $event = $res->webhook_event->event_type;
            
            switch ($event) {
                case 'PAYMENT.CAPTURE.COMPLETED':
                    $name = $this->em->getRepository(Name::class)->findOneByCaptureId($resource->id);

                    if(!$name->getOrder()->getMailPayPalSent()) {
                        $name->getOrder()->setMailPayPalSent(true);
                        $this->em->flush();
                        $this->mailer->sendConfirmPayment($name, $event);
                    }

                    $outputCode = 201;
                    break;
                //case 'payment_intent.payment_failed':
                //case 'PAYMENT.ORDER.CANCELLED':
                //    $outputCode = 202;
                //    break;
                //case 'PAYMENT.ORDER.CREATED': //??? PAYMENTS.PAYMENT.CREATED, ??? CHECKOUT.ORDER.APPROVED
                //    $outputCode = 203;
                //    break;
                //case 'PAYMENT.CAPTURE.REFUNDED':
                //    $outputCode = 204;
                //    break;
                case 'DISPUTES.UPDATED':
                case 'DISPUTES.CREATED':
                case 'DISPUTES.RESOLVED':
                case 'RISK.DISPUTE.CREATED':
                    $this->mailer->sendAlertPayment("PayPal", $event, json_encode($resource, JSON_PRETTY_PRINT));
                    $outputCode = 205;
                    break;
                default:
                    $outputCode = 206;
            }
        }

        return $outputCode;
    }

    public function refund($captureId)
    {
        try{
            $request = new CapturesRefundRequest($captureId);
            //$request->body = self::buildRequestBody();
            $client = $this->client();
            $response = $client->execute($request);

            return $response;
        }
        catch (\PayPalHttp\HttpException $ex) {} 
        catch (\Exception $ex) {}

        return null;
    }
}