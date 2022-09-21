<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use App\Entity\Delay;
use App\Entity\DiscountCode;
use App\Entity\Grade;
use App\Entity\Name;
use App\Entity\Order;
use App\Entity\PaymentMethod;
use App\Entity\Size;
use App\Entity\TextFont;

use App\Exception\NamePositionException;

use App\Service\Payments;
use App\Service\Tools;
use App\Service\PayPal;
use App\Service\Stripe;
use App\Service\Mailer;

class NameController extends AbstractController
{
    private $algoHash;
    private $saltKeyName;

    public function __construct(string $algoHash, string $saltKeyName)
    {
        $this->algoHash = $algoHash;
        $this->saltKeyName = $saltKeyName;
    }

    /**
     * @Route("/Edition", name="edition", methods={"GET"})
     */
    public function edition(Request $request)
    {
        $locale = $request->getLocale();
        $language = Tools::getLanguage($locale);
        $currency = Payments::getCurrency($request);

        $names = $this->getDoctrine()
        ->getRepository(Name::class)
        ->findAllNames();

        $sizes = $this->getDoctrine()
        ->getRepository(Size::class)
        ->findAll();

        $textFonts = $this->getDoctrine()
        ->getRepository(TextFont::class)
        ->findAll();

        $delays = $this->getDoctrine()
        ->getRepository(Delay::class)
        ->findAll();

        return $this->render('name/index.html.twig', [
            "language" => $language,
            "currency" => $currency,
            "names" => $names,
            "sizes" => $sizes,
            "textFonts" => $textFonts,
            "delays" => $delays
        ]);
    }

    /**
     * @Route("/getNamesEdition", name="getNamesEdition" , methods={"GET"})
     */
    public function getNamesEdition()
    {
        $names = $this->getDoctrine()
            ->getRepository(Name::class)
            ->findAllNames();
        return $this->render('names.html.twig', ["names" => $names]);
    }

    /**
     * @Route("/capturePayPalPayment", name="capturePayPalPayment" , methods={"POST"})
     */
    public function capturePayPalPayment(Request $request, PayPal $payPal, Tools $tools, TranslatorInterface $translator)
    {
        $orderId = $request->request->get('orderID');
        $name = $this->getDoctrine()->getRepository(Name::class)->findOneByPaymentId($orderId);

        $error = 1;
        $message = $translator->trans('addName.error');

        if($name)
        {
            if(!$name->getDeletion())
            {
                $response = $payPal->capturePayment($orderId);
                if($response && $response->result->status === 'COMPLETED')
                {
                    $name->getOrder()->setPayPalCaptureId($response->result->purchase_units[0]->payments->captures[0]->id);
                    $this->getDoctrine()->getManager()->flush();
                    $tools->confirmName($name);
        
                    $error = 0;
                    $message = $translator->trans('addName.success');
                }
            }
            else{
                $message = $translator->trans('addName.errorPayPalTime');
            }
        }
  
        return new JsonResponse(array(
            'error' => $error,
            'message' => $message
        ),
        200);
    }

    /**
     * @Route("/removeName", name="removeName" , methods={"POST"})
     */
    public function removeName(Request $request, Tools $tools)
    {
        $orderId = $request->request->get('orderID');
        $name = $this->getDoctrine()->getRepository(Name::class)->findOneByPaymentId($orderId);

        if(!$name->getOrder()->getConfirmation() && !$name->getOrder()->getDeletion())
        {
            $tools->deleteName($name);
        }

        return new JsonResponse(array(), 200);
    }
    
    /**
     * @Route("/addName", name="addName", methods={"POST"})
     */
    public function addName(Request $request, ValidatorInterface $validator,
                                    TranslatorInterface $translator, Payments $payments,
                                    Tools $tools, Stripe $stripe, PayPal $payPal)
    {
        $submittedToken = $request->request->get('tokenCsrf');

        if($request->isXmlHttpRequest() && $this->isCsrfTokenValid('add-name', $submittedToken))
        {
            $paymentMethod = htmlspecialchars($request->request->get('paymentMethod'));
            $buyerName = htmlspecialchars($request->request->get('buyerName'));
            $buyerEmail = htmlspecialchars($request->request->get('buyerEmail'));
            $name = htmlspecialchars($request->request->get('name'));
            $color = htmlspecialchars($request->request->get('color'));
            $size = intval(htmlspecialchars($request->request->get('size'))); //augmente la securite juste en gardant les chiffres et en enlevant le "pt"
            $textFont = htmlspecialchars($request->request->get('textFont'));
            $positionX = htmlspecialchars($request->request->get('positionX'));
            $positionY = htmlspecialchars($request->request->get('positionY'));
            $width = htmlspecialchars($request->request->get('width'));
            $height = htmlspecialchars($request->request->get('height'));
            $bold = intval(htmlspecialchars($request->request->get('bold')) == 1);
            $italic = intval(htmlspecialchars($request->request->get('italic')) == 1);
            $delay = htmlspecialchars($request->request->get('delay')); //le delay est un nombre de jours
            $price = htmlspecialchars($request->request->get('price'));
            $priceBeforeDiscount = htmlspecialchars($request->request->get('priceBeforePromotion'));
            $grade = "Normal"; 
            
            $discountCode = null;
            $error = 0;

            if(null !== $request->request->get('codePromotion'))
            {
                $code = strtoupper(htmlspecialchars($request->request->get('codePromotion')));    
                list($error, $discountCode) = $payments->verifyDiscountCode($code, $validator, false);
            }
            
            if(!$error)
            {
                $locale = $request->getLocale();
                $language = $tools->getLanguage($locale);

                $sizeFound = $this->getDoctrine()->getRepository(Size::class)->findOneBySize($size);
                $textFontFound = $this->getDoctrine()->getRepository(TextFont::class)->findOneByTextFont($textFont);     
                $delayFound = $this->getDoctrine()->getRepository(Delay::class)->findOneByNbDays($delay);
                $gradeFound = $this->getDoctrine()->getRepository(Grade::class)->findOneByType($grade);
                $paymentMethodFound = $this->getDoctrine()->getRepository(PaymentMethod::class)->findOneByMethodName($paymentMethod);
    
                if($sizeFound && $textFontFound && $delayFound && $gradeFound && $paymentMethodFound
                    && preg_match(Name::VALID_REGEX_COLOR, $color) && preg_match(Name::VALID_REGEX_NAME, $name))
                {
                    $bold = ($bold && $textFontFound->getBoldAvailable());
                    $italic = ($italic && $textFontFound->getItalicAvailable());

                    $myName = new Name($name, $color, $bold, $italic, $sizeFound,
                                        $textFontFound, $delayFound, $gradeFound, $positionX, $positionY, $width, $height);

                    if($tools->checkPosition($myName) && $tools->checkWidthHeight($myName))
                    {
                        $currency = $payments->getCurrency($request);
                        $currencySymbol = $payments->currencyToSymbol($currency);

                        list($priceBefore, $priceAfter, $errorDiscount) = $payments->calculatePrice($myName, $currency, $discountCode);
                        
                        if(!$errorDiscount)
                        {
                            if($price === (string)$priceAfter)
                            {
                                //vérification des informations passées en paramètres suivant la méthode de paiement
                                if($paymentMethodFound->getMethodName() === "StripeCard" && null === $request->request->get('stripePaymentMethod'))
                                {
                                    $error = 1;
                                }

                                if(!$error)
                                {
                                    $order = new Order();
                                    $order->setBuyerName($buyerName);
                                    $order->setBuyerEmail($buyerEmail);
                                    $order->setIp($request->getClientIp());
                                    $order->setOrderNumber(random_int(100000000, 999999999));
                                    $order->setPrice($priceAfter);
                                    $order->setPaid(false);
                                    $order->setPriceBeforeDiscount($priceBefore);
                                    $order->setCurrency($currency);
                                    $order->setLanguage($language);
                                    $order->setPaymentMethod($paymentMethodFound);
                                    $order->setDiscountCode($discountCode);
                                    
                                    $myName->setSecretKey(hash($this->algoHash, time().mt_rand().$this->saltKeyName));
                                    $myName->setOrder($order);
                                    
                                    //On vérifie l'order et le name
                                    $errorsOrder = $validator->validate($order);                                        
                                    if (count($errorsOrder) == 0) 
                                    {
                                        $errorsName = $validator->validate($myName);                                   
                                        if (count($errorsName) == 0) 
                                        {
                                            try {
                                                $em = $this->getDoctrine()->getManager();
                                                $em->persist($myName);
                                                $em->flush();
                                            }
                                            catch(NamePositionException $exception)
                                            {
                                                return new JsonResponse(array(
                                                    'error' => 1,
                                                    'message' => $translator->trans('addName.placeTaken')
                                                ),
                                                200);
                                            }

                                            switch($paymentMethodFound->getMethodName())
                                            {
                                                case "StripeCard":
                                                    $stripePaymentMethod = $request->request->get('stripePaymentMethod');
                                                    list($err, $client_secret) = $stripe->createOrder($myName, $stripePaymentMethod);
                                                    
                                                    if(!$err)
                                                    {
                                                        return new JsonResponse(array(
                                                            'error' => 0,
                                                            'message' => $translator->trans('addName.success'),
                                                            'clientSecret' => $client_secret
                                                        ),
                                                        200);
                                                    }
                                                    else {
                                                        $message = $translator->trans('addName.stripe.error.default');
                                                    }
                                                break;
                                                case "PayPal":
                                                    list($err, $paymentId) = $payPal->createOrder($myName);

                                                    if(!$err)
                                                    {
                                                        return new JsonResponse(array(
                                                            'error' => 0,
                                                            'message' => $translator->trans('addName.success'),
                                                            'clientSecret' => $paymentId
                                                        ),
                                                        200);
                                                    }
                                                    else{
                                                        $message = $translator->trans('addName.error');
                                                    }
                                                break;
                                                default:
                                                    $message = $translator->trans('addName.paymentMethodIncorrect');
                                                break;
                                            }
                                        }
                                        else{
                                            $message = (string) $errorsName[0]->getMessage();
                                        }
                                    }
                                    else{
                                        $message = (string) $errorsOrder[0]->getMessage();
                                    }
                                }
                                else{
                                    $message = $translator->trans('addName.stripe.tokenMissing');
                                }
                            }
                            else{
                                $message = $translator->trans('addName.priceIncorrect');
                            }
                        }
                        else{
                            $message = $translator->trans('addName.discountCodeNotApplicable');
                        }
                    }
                    else{
                        $message = $translator->trans('addName.positionIncorrect');
                    }
                }
                else{
                    $message = $translator->trans('addName.fieldMissing');
                }
            }
            else{
                $message = $translator->trans('addName.discountCodeIncorrect');
            }
        }
        else{
            $message = $translator->trans('addName.error');
        }
        
        return new JsonResponse(array(
            'error' => 1,
            'message' => $message
        ),
        200);
    }
}
