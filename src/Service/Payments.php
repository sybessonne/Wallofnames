<?php

namespace App\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\EntityManagerInterface;

use App\Entity\DiscountCode;
use App\Entity\Name;

class Payments
{
    private $em;
	private $encoding;
	private $priceArray;

    public function __construct(EntityManagerInterface $entityManager, string $encoding, array $priceArray)
    {
        $this->em = $entityManager;
		$this->encoding = $encoding;
		$this->priceArray = $priceArray;
	}

    public function getCurrency(Request $request)
	{
		$ip = $request->getClientIp();
		$details = json_decode(file_get_contents("http://ipinfo.io/{$ip}"));
		
		$country = "";
		try{
			$country = $details->country;
			$currency = "";
		}
		catch (\Exception $ex) {}
		
		switch ($country) {
			case 'US': //USA
				$currency = "USD";
			break;
			default:
				$currency = "EUR";
		}

		return $currency;	
	}

	public static function currencyToSymbol(string $currency)
	{
		$symbol = "";
		switch ($currency) {
			case 'USD':
				$symbol = "$";
				break;
			default:
				$symbol = "€";
		}

		return $symbol;
	}

	public static function priceFormat($price, $language)
	{
		switch ($language) {
			case 'fr':
				$price = number_format($price, 2, ',', ' ');
				break;
			default:
				$price = number_format($price, 2, '.', ',');		
		}

		return $price;
	}

	public function verifyDiscountCode(string $code, ValidatorInterface $validator, bool $totalCheck)
	{
		$discountCode = new DiscountCode();
		$discountCode->setCode($code);
		
		$errors = $validator->validate($discountCode);
		$error = 1;
            
		if (count($errors) == 0) 
		{
			$discount = $this->em->getRepository(DiscountCode::class)
				->verifyDiscountCode($discountCode->getCode(), $totalCheck);

			if($discount)
			{
				$error = 0;
			}
		}
		
		return array($error, $discount);
	}

	public function calculatePrice(Name $name, string $currency, ?DiscountCode $discountCode)
	{
		$price = 0;
		$length = mb_strlen($name->getName(), $this->encoding);

		if($name->getColor() != "#000" && $name->getColor() != "#000000"){
			$price += $this->priceArray["color"] * $this->priceArray["colorCoef"];
		} 

		$price += $name->getTextFont()->getPrice() * $this->priceArray['textFontCoef'];
		$price += $name->getDelay()->getPrice() * $this->priceArray['delayCoef'];

		//on calcul le price en prenant en compte le nombre de jour que le nom va etre affiché
		$price += $name->getSize()->getPrice() * $this->priceArray['sizeCoef']
					* $name->getSize()->getSize() * $this->priceArray['sizeExpoCoef'] * $name->getDelay()->getNbDays();

		$price += $name->getGrade()->getPrice() * $this->priceArray['gradeCoef'];

		if($name->getBold()){
			$price += $this->priceArray['bold'] * $this->priceArray['boldCoef'];
		}
		if($name->getItalic()){
			$price += $this->priceArray['italic'] * $this->priceArray['italicCoef'];
		}

		if($price != 0){		
			$price = $price * $length * $this->priceArray['totalCoef'];
		}
		else{
			$price = $length * $this->priceArray['totalCoef'];
		}

		if($price != 0){
			$price = $price - 0.01 * $length;

			// Ce calcul permet de prendre en compte les frais de commission de la méthode de paiement (StripeCard ou PayPal)
			$price = $price * $this->priceArray['multiplierCosts'] + $this->priceArray['additionalCosts'];

			//Si la devise n'est pas l'euro, alors il faut calculer le price suivant la bonne devise,
			// pour cela il faut récupérer le taux actuel
			if($currency != $this->priceArray['defaultCurrency']){
				$price = $this->calculateCurrencyRate($price, $currency);
			}
		}
		
		$priceBefore = round($price, 2);
		$priceAfter = $priceBefore;
		$error = 0;

		if($discountCode && $discountCode->getCode() !== ""){
			if($priceBefore >= $discountCode->getMinPrice()){
				$priceAfter = round(($price - $price * $discountCode->getDiscount() / 100), 2);
			}
			else {
				$error = 1;
			}
		}

		return array($priceBefore, $priceAfter, $error);
	}

	//Fonction qui récupère le taux de change suivant la devise et qui calcul le prix suivant ce taux
	function calculateCurrencyRate($price, $currency)
	{
		$defaultCurrency = $this->priceArray["defaultCurrency"];
		$exchangeCosts = $this->priceArray['exchangeRate'];
		$rate = $this->priceArray['defaultRate']; 

		$req_url = 'https://api.exchangeratesapi.io/latest?base='.$defaultCurrency;
		$response_json = file_get_contents($req_url);

		if(false !== $response_json) {
			try {
				$response_object = json_decode($response_json, true);
				$rate = $response_object["rates"][$currency];
			}
			catch(\Exception $e) {}
		}

		return $price * $rate * $exchangeCosts;
	}
}