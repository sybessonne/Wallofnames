<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Entity\Name;

use App\Service\Stripe;
use App\Service\PayPal;
use App\Service\Mailer;
use App\Service\Tools;

class CronController extends AbstractController
{
    private $timeToPay; 

    public function __construct(int $timeToPay)
    {
        $this->timeToPay = $timeToPay;
    }

    /**
     * @Route("/purgeName_Df9ACAcFE69BB7f7b9DFfCb21Adbdc", name="purges_names")
     */
    public function purge(Stripe $stripe, PayPal $payPal, Mailer $mailer, Tools $tools)
    {
        $names = $this->getDoctrine()
                    ->getRepository(Name::class)
                    ->findNamesNotPaidInTime($this->timeToPay);
    
        foreach($names as $name)
        {
            $out = false;
            switch($name->getOrder()->getPaymentMethod()->getMethodName())
            {
                case 'StripeCard':
                    $out = $stripe->cancel($name->getOrder()->getPaymentId());
                    break;
                case 'PayPal':
                    $tools->deleteName($name);
                    $mailer->sendCancelPayment($name, "PAYMENT.CANCELED");
                break;
            }
      
            if(!$out) {
                //send A mail to me to tell that there is a problem during delete one name
                $mailer->sendErrorPurgeName($name);
            }
        }

        $this->getDoctrine()->getManager()->flush();
        
        return new JsonResponse(array(), 200);
    }
}
