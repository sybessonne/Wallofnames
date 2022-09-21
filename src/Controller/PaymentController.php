<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Service\Payments;
use App\Service\Tools;
use App\Service\Stripe;

use App\Entity\Delay;
use App\Entity\DiscountCode;
use App\Entity\Grade;
use App\Entity\Name;
use App\Entity\Size;
use App\Entity\TextFont;

class PaymentController extends AbstractController
{
    private $addressContact;

    public function __construct(array $addresses)
    {
        $this->addressContact = $addresses['contact'];
    }

    /**
     * @Route("/emails/receipts/{receiptNum}", name="displayReceipt", methods={"GET"})
     */
    public function displayReceipt(Request $request, string $receiptNum,
                                    TranslatorInterface $translator, Tools $tools, Stripe $stripe)
    {
        $name = $this->getDoctrine()->getRepository(Name::class)->findOneByReceipt($receiptNum);

        if($name)
        {
            $confirmedDate = $name->getConfirmationDate()->format($translator->trans('mail.addName.body.addedDate'));
         
            switch($name->getOrder()->getPaymentMethod()->getMethodName())
            {
                case "StripeCard":
                    $paymentIntent = $stripe->getPayment($name->getOrder()->getPaymentId());
                    $paymentMethod = $paymentIntent->charges->data[0]->payment_method_details->card->brand;
                    $last4Numbers = $paymentIntent->charges->data[0]->payment_method_details->card->last4;
                break;
                case "PayPal":
                    $paymentMethod = "";
                    $last4Numbers = "PayPal";
                break;
                default:
                    $paymentMethod = "";
                    $last4Numbers = "";
            }
                        
            $datas = array("name" => $name,
                "confirmedDate" => $confirmedDate,
                "paymentMethod" => $paymentMethod,
                "last4Numbers" => $last4Numbers,
                "addressContact" => $this->addressContact,
                "buyerLanguage" => $name->getOrder()->getLanguage()
            );
                
            return $this->render('emails/receipt.html.twig', $datas);
        }
        else{
            throw new NotFoundHttpException("Page not found");
        }
    }

    /**
     * @Route("/verifyPromotion", name="verifyPromotion", methods={"GET"})
     */
    public function verifyPromotion(Request $request, ValidatorInterface $validator,
                                    TranslatorInterface $translator, Payments $payments)
    {
        if($request->isXmlHttpRequest())
        {
            $code = strtoupper(htmlspecialchars($request->query->get('codePromotion')));
            list($error, $discountCode) = $payments->verifyDiscountCode($code, $validator, true);

            if($error == 0)
            {
                $locale = $request->getLocale();
                $currency = $payments->getCurrency($request);
                $currencySymbol = $payments->currencyToSymbol($currency);

                return new JsonResponse(array(
                    'error' => 0,
                    'message' => $translator->trans('verifyPromotion.correct', ['%discountAmount%' => $discountCode->getDiscount()]),
                    'codePromotion' => $discountCode->getCode(),
                    'promotionText' => $translator->trans('verifyPromotion.chain', 
                                                            ['%discountCode%' => $discountCode->getCode(),
                                                            '%discountAmount%' => $discountCode->getDiscount(),
                                                            '%minPrice%' => $discountCode->getMinPrice(),
                                                            '%currencySymbol%' => $currencySymbol]),
                    'promotionMinPrice' => $discountCode->getMinPrice(),
                    'promotion' => $translator->trans('verifyPromotion.discount', ['%discountAmount%' => $discountCode->getDiscount()])
                ),
                200);
            }
        }

        return new JsonResponse(array(
            'error' => 1,
            'message' => $translator->trans('verifyPromotion.incorrect')
        ),
        200);
    }

    /**
     * @Route("/getPrice", name="getPrice", methods={"GET"})
     */
    public function getPrice(Request $request, ValidatorInterface $validator,
                                    TranslatorInterface $translator, Payments $payments)
    {
        if($request->isXmlHttpRequest())
        {
            $name = htmlspecialchars($request->query->get('name'));
            $color = htmlspecialchars($request->query->get('color'));
            $size = intval(htmlspecialchars($request->query->get('size'))); //augmente la securite juste en gardant les chiffres et en enlevant le "pt"
            $textFont = htmlspecialchars($request->query->get('textFont'));
            $bold = intval(htmlspecialchars($request->query->get('bold')) == 1);
            $italic = intval(htmlspecialchars($request->query->get('italic')) == 1);
            $delay = htmlspecialchars($request->query->get('delay')); //le delai un nombre de jours
            $grade = "Normal"; 

            $error = 0;
            $price = 0;
            $chainPrice = "";
            $priceBeforeDiscount = "";

            $discountCode = new DiscountCode();

            if(null !== $request->query->get('codePromotion'))
            {
                $code = strtoupper(htmlspecialchars($request->query->get('codePromotion')));    
                list($error, $discountCode) = $payments->verifyDiscountCode($code, $validator, false);
            }

            if($error == 0)
            {
                $locale = $request->getLocale();
                $language = Tools::getLanguage($locale);

                $sizeFound = $this->getDoctrine()->getRepository(Size::class)->findOneBySize($size);
                $textFontFound = $this->getDoctrine()->getRepository(TextFont::class)->findOneByTextFont($textFont);     
                $delayFound = $this->getDoctrine()->getRepository(Delay::class)->findOneByNbDays($delay);
                $gradeFound = $this->getDoctrine()->getRepository(Grade::class)->findOneByType($grade);
    
                if($sizeFound && $textFontFound && $delayFound && $gradeFound && 
                    preg_match(Name::VALID_REGEX_COLOR, $color) && (preg_match(Name::VALID_REGEX_NAME, $name) || $name === ""))
                {
                    $currency = $payments->getCurrency($request);
                    $currencySymbol = $payments->currencyToSymbol($currency);

                    $myName = new Name($name, $color, $bold, $italic, $sizeFound, $textFontFound, $delayFound, $gradeFound);

                    list($priceBefore, $priceAfter, $errorDiscount) = $payments->calculatePrice($myName, $currency, $discountCode);

                    $priceBeforeFormated = $payments->priceFormat($priceBefore, $language);
                    $priceAfterFormated = $payments->priceFormat($priceAfter, $language);

                    return new JsonResponse(array(
                        'error' => 0,
                        'price' => $priceAfter,
                        'priceBeforePromotion' => $priceBefore,
                        'stringPrice' => $translator->trans('getPrice', ['%price%' => $priceAfterFormated,
                                                                        '%currencySymbol%' => $currencySymbol]),
                        'stringPriceNoPromotion' => $translator->trans('getPrice', ['%price%' => $priceBeforeFormated,
                                                                        '%currencySymbol%' => $currencySymbol])
                    ),
                    200);
                }
            }
        }

        return new JsonResponse(array(
            'error' => 0,
            'price' => 0,
            'priceBeforePromotion' => 0,
            'stringPrice' => "",
            'stringPriceNoPromotion' => ""
        ),
        200);
    }
}
