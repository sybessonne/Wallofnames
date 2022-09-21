<?php

namespace App\Controller;

use Exception;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use App\Entity\TextFont;
use App\Service\Tools;

class AdminController extends EasyAdminController
{
    private $validExtensions;
    private $fontFolder;
    private $tools;
        
    public function __construct(array $validExtensions,string $fontFolder, Tools $tools)
    {
        $this->validExtensions = $validExtensions;
        $this->fontFolder = $fontFolder;
        $this->tools = $tools;
    }
    
    protected function persistEntity($entity)
    {
        if($entity instanceof TextFont)
        {           
            $bold = $entity->getBoldAvailable();
            $italic = $entity->getItalicAvailable();
    
            $f1 = $entity->getFileBase();
            $f2 = $entity->getFileBold();
            $f3 = $entity->getFileItalic();
            $f4 = $entity->getFileBI();

            //if error in checkbox and file empty
            if(!$f1 ||
            ($bold && !$f2) || 
            ($italic && !$f3) ||
            ($bold && $italic && !$f4))
            {
                throw new Exception("Erreur : un fichier obligatoire n'a pas été renseigné");
            }

            $ext1 = ($f1) ? $f1->getClientOriginalExtension() : "";
            $ext2 = ($f2) ? $f2->getClientOriginalExtension() : "";
            $ext3 = ($f3) ? $f3->getClientOriginalExtension() : "";
            $ext4 = ($f4) ? $f4->getClientOriginalExtension() : "";

            //extension incorrect
            if(!in_array($ext1, $this->validExtensions) || 
            ($f2 && !in_array($ext2, $this->validExtensions)) || 
            ($f3 && !in_array($ext3, $this->validExtensions)) || 
            ($f4 && !in_array($ext4, $this->validExtensions)))
            {
                throw new Exception("Erreur : Mauvaise extension renseignée");
            }
    
            //Not the same extension for all textfont
            if(($f2 && $ext1 !== $ext2) ||
                ($f3 && $ext1 !== $ext3) ||
                ($f4 && $ext1 !== $ext4))
            {
                throw new Exception("Erreur : Les extensions ne sont pas identiques");
            }

            $entity->setExtension($ext1);

            $f1->move($this->fontFolder, $entity->getTextFontFile().'.'.$ext1);
            if($f2){ $f2->move($this->fontFolder, $entity->getTextFontFile().'_b.'.$ext1); }
            if($f3){ $f3->move($this->fontFolder, $entity->getTextFontFile().'_i.'.$ext1); }
            if($f4){ $f4->move($this->fontFolder, $entity->getTextFontFile().'_b_i.'.$ext1); }
        }
        
        parent::persistEntity($entity);

        if($entity instanceof TextFont)
        {
            $this->tools->createCSSTextFontFile();
        }
    }
}
