<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\Stripe;
use App\Service\PayPal;

class WebhookController extends AbstractController
{
    /**
     * @Route("/stripeWebhook_sd5e8vt1az56erFrg61fdFHCGep45c", name="webhookStripe")
     */
    public function stripeWebhook(Request $request, Stripe $stripe)
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $request->server->get('HTTP_STRIPE_SIGNATURE');
        
        $outputCode = $stripe->dispatchEvent($payload, $sig_header);
        
        return new JsonResponse(array(), $outputCode);
    }

    /**
     * @Route("/payPalWebhook_eob1DF4hg55ns6P9DErbMv56i25tpx", name="webhookPayPal")
     */
    public function payPalWebhook(Request $request, PayPal $payPal)
    {
        $headers = array_change_key_case($request->headers->all(), CASE_UPPER);
        $body = file_get_contents('php://input');

        $output =  $payPal->dispatchEvent($body, $headers);

        return new JsonResponse(array(), $outputCode);
    }
}
