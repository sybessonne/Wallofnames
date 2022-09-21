<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Name;
use App\Entity\Order;
use App\Entity\PaymentMethod;

use App\Service\Mailer;
use App\Service\PayPal;
use App\Service\Stripe;
use App\Service\Tools;

/**
* @Route("/admin_F64dfEE0d6fA8F0CEab8970039ccAB")
*/
class AdminNameController extends AbstractController
{
    /**
     * @Route("/validateNames", name="validate_names", methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function validateNames()
    {
        $names = $this->getDoctrine()
            ->getRepository(Name::class)
            ->findNamesToValidate();

        $namesForWall = $this->getDoctrine()
            ->getRepository(Name::class)
            ->findNamesToValidateForWall();

        return $this->render('security/validateNames.html.twig', ["names" => $names, "namesForWall" => $namesForWall]);
    }

    /**
     * @Route("/validateName", name="validate_name", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function validateName(Request $request)
    {
        $id = $request->request->get('idName');
        $secretKey = $request->request->get('secretKey');

        $name = $this->getDoctrine()
            ->getRepository(Name::class)
            ->findOneBy(array('id' => $id, 'secretKey' => $secretKey));
            
        if (!$name) {
            throw $this->createNotFoundException(
                'No name found for id '.$id
            );
        }

        $name->setValidation(true);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('validate_names');
    }

    /**
     * @Route("/disableUrl", name="disable_url", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function disableUrl(Request $request)
    {
        $id = $request->request->get('idName');
        $secretKey = $request->request->get('secretKey');

        $name = $this->getDoctrine()
            ->getRepository(Name::class)
            ->findOneBy(array('id' => $id, 'secretKey' => $secretKey));
            
        if (!$name) {
            throw $this->createNotFoundException(
                'No name found for id '.$id
            );
        }

        $name->setUrlEnabled(false);
        $this->getDoctrine()->getManager()->flush();

        //TODO : peut être envoyé un mail à l'acheteur pour lui dire que le lien à été désactiver et lui donner un lien lui permettant d'en faire un autre

        return $this->redirectToRoute('validate_names');
    }

    /**
     * @Route("/deleteName", name="delete_name", methods={"POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function deleteName(Request $request, Stripe $stripe, PayPal $payPal, Tools $tools, Mailer $mailer)
    {
        $id = $request->request->get('idName');
        $secretKey = $request->request->get('secretKey');
        $reason = $request->request->get('reason');

        $name = $this->getDoctrine()
            ->getRepository(Name::class)
            ->findOneBy(array('id' => $id, 'secretKey' => $secretKey));
            
        if (!$name) {
            throw $this->createNotFoundException(
                'No name found for id '.$id
            );
        }

        $name->getOrder()->setRefundReason($reason);
        $this->getDoctrine()->getManager()->flush();

        $refundStripe = null;
        $refundPayPal = null;
        if($name->getOrder()->getPaymentMethod()->getMethodName() === "StripeCard")
        {
            $refundStripe = $stripe->refund($name->getOrder()->getPaymentId());
        }
        else if($name->getOrder()->getPaymentMethod()->getMethodName() === "PayPal")
        {
            $refundPayPal = $payPal->refund($name->getOrder()->getPayPalCaptureId());
            if($refundPayPal && $refundPayPal->result->status === 'COMPLETED')
            {
                $name->getOrder()->setPayPalRefundId($refundPayPal->result->id);
                $this->getDoctrine()->getManager()->flush();
                
                if($tools->deleteName($name)){
                    $mailer->sendRefundPayment($name, "PAYMENT.CAPTURE.REFUNDED", 
                                                json_encode($refundPayPal->result, JSON_PRETTY_PRINT));
                }
            }
        }
        else
        {
            throw $this->createNotFoundException(
                'PaymentMethod not found for name id : '.$id
            );
        }

        return $this->render('security/deleteName.html.twig', ['name' => $name, 
                                                            'refundStripe' => $refundStripe, 
                                                            'refundPayPal' => $refundPayPal]);
    }
}
