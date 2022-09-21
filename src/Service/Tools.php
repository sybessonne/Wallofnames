<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Name;
use App\Entity\TextFont;

class Tools
{
    private $em;
	private $imageWidth;
	private $imageHeight;
	private $checkName;
	private $fontFolder;
	private $pathCSSTextFonts;
	private $algoHash;
    private $saltKeyReceipt;
	private $translator;
	
    public function __construct(EntityManagerInterface $entityManager, int $imageWidth, int $imageHeight,
								array $checkName, string $fontFolder, string $pathCSSTextFonts, 
								string $algoHash, string $saltKeyReceipt, TranslatorInterface $translator)
    {
        $this->em = $entityManager;
		$this->imageWidth = $imageWidth;
		$this->imageHeight = $imageHeight;
		$this->checkName = $checkName;
		$this->fontFolder = $fontFolder;
		$this->pathCSSTextFonts = $pathCSSTextFonts;
		$this->algoHash = $algoHash;
        $this->saltKeyReceipt = $saltKeyReceipt;
		$this->translator = $translator;
	}

	public static function getLanguage(string $locale)
	{
		$lang = strtolower(substr($locale, 0, 2));
		$language = "";

		switch ($lang) {
			case 'fr':
				$language = "fr";
			break;
			default:
				$language = "en";
		}

		return $language;
	}

	function deleteName(Name $name)
	{
		if(!$name->getDeletion()) {
			$name->setDeletion(true);
			$name->setDeletionDate(new \DateTime());
			$this->em->flush();
		
			return true; //the name has been deleted successfully
		}
		else{
			return false; //the name has already been deleted
		}
	}
	
	function checkPosition(Name $name)
	{
		$output = true;

		if($name->getPositionX() < 0 || $name->getPositionY() < 0 || 
			$name->getWidth() <= 0 || $name->getHeight() <= 0 || 
			$name->getPositionX() > $this->imageWidth || $name->getPositionY() > $this->imageHeight)
		{
			$output = false;
		}
		else
		{
			if($name->getWidth() + $name->getPositionX() > $this->imageWidth || 
				$name->getHeight() + $name->getPositionY() > $this->imageHeight)
			{
				$output = false;
			}
		}

		return $output;
	}

	function checkWidthHeight(Name $name)
	{
		$output = true;		

		$b_extension = ($name->getBold()) ? '_b' : '';
		$i_extension = ($name->getItalic()) ? '_i' : '';
		$font = $this->fontFolder.$name->getTextFont()->getTextFontFile().$b_extension.$i_extension.'.'.$name->getTextFont()->getExtension();

		//recuperation du fichier de police
		$coords = imagettfbbox($name->getSize()->getSize(), 0, $font, $name->getName());

		//petit calcul de coefficient pour augmenter le height
		$calculationCoef = 1;
		if($name->getSize()->getSize() > $this->checkName['calculationBasisSize']){
			$calculationCoef = ($name->getSize()->getSize() - $this->checkName['calculationBasisSize']) / 100 + 1;
		}

		//les coordonnées de largeur et hauteur sont complètement fausse on le marque
		if(abs($name->getWidth() - ($coords[2] - $coords[0]) * $this->checkName['coeffDeviationX']) > $this->checkName['deviationX'] * $calculationCoef || 
			abs($name->getHeight() - ($coords[1] - $coords[7]) * $this->checkName['coeffDeviationY']) > $this->checkName['deviationY'] * $calculationCoef)
		{
			$output = false;
		}

		return $output;
	}

	public function confirmName(Name $name)
	{
		if(!$name->getConfirmation()) {
			$receipt = hash($this->algoHash, time().mt_rand().$this->saltKeyReceipt);
			$name->getOrder()->setReceipt($receipt);

			$name->setConfirmation(true);
			$name->setConfirmationDate(new \DateTime());
			$name->getOrder()->setPaid(true);
			if($name->getOrder()->getDiscountCode())
			{
				$name->getOrder()->getDiscountCode()->setNbUses($name->getOrder()->getDiscountCode()->getNbUses() - 1);
			}
			$this->em->flush();
		
			return true; //the name has been confirmed successfully
		}
		else{
			return false; //the name has already been confirmed
		}
	}

	public function createCSSTextFontFile()
	{
		$fileTextFont = fopen($this->pathCSSTextFonts, 'w');
        $textFonts = $this->em->getRepository(TextFont::class)->findAll();

		foreach ($textFonts as $textFont) 
		{
			fputs($fileTextFont, "@font-face {\n\tfont-family: ".$textFont->getTextFont().
										";\n\tsrc: url('Fonts/".$textFont->getTextFontFile().
										".".$textFont->getExtension()."');\n}\n\n");
			if($textFont->getBoldAvailable())
			{
				fputs($fileTextFont, "@font-face {\n\tfont-family: ".$textFont->getTextFont().
											"B;\n\tsrc: url('Fonts/".$textFont->getTextFontFile().
											"_b.".$textFont->getExtension()."');\n}\n\n");
			}
			if($textFont->getItalicAvailable())
			{
				fputs($fileTextFont, "@font-face {\n\tfont-family: ".$textFont->getTextFont().
											"I;\n\tsrc: url('Fonts/".$textFont->getTextFontFile().
											"_i.".$textFont->getExtension()."');\n}\n\n");
			}
			if($textFont->getBoldAvailable() && $textFont->getItalicAvailable())
			{
				fputs($fileTextFont, "@font-face {\n\tfont-family: ".$textFont->getTextFont().
											"BI;\n\tsrc: url('Fonts/".$textFont->getTextFontFile().
											"_b_i.".$textFont->getExtension()."');\n
											font-display: swap;
										}\n\n");
			}
		}

		fclose($fileTextFont);
	}
}