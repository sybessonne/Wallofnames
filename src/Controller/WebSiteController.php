<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Contact;
use App\Entity\Name;

use App\Form\ContactType;

use App\Service\Mailer;
use App\Service\Tools;

class WebSiteController extends AbstractController
{
    /**
     * @Route("/", name="index" , methods={"GET"})
     */
    public function index(Request $request)
    {
        $locale = $request->getLocale();
        $language = Tools::getLanguage($locale);

        $names = $this->getDoctrine()
            ->getRepository(Name::class)
            ->findValidatedNames();
        return $this->render('web_site/index.html.twig', ["names" => $names, "language" => $language]);
    }

    /**
     * @Route("/getNamesIndex", name="getNamesIndex" , methods={"GET"})
     */
    public function getNamesIndex()
    {
        $names = $this->getDoctrine()
            ->getRepository(Name::class)
            ->findValidatedNames();
        return $this->render('names.html.twig', ["names" => $names]);
    }

    /**
     * @Route("/CGV", name="CGV" , methods={"GET"})
     */
    public function CGV(Request $request)
    {
        $locale = $request->getLocale();
        $language = Tools::getLanguage($locale);
                
        return $this->render('web_site/CGV.html.twig', ["language" => $language]);
    }

    /**
     * @Route("/Contact", name="contact" , methods={"GET", "POST"})
     */
    public function contact(Request $request, ValidatorInterface $validator,
                            TranslatorInterface $translator, Mailer $mailer)
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        $locale = $request->getLocale();
        $language = Tools::getLanguage($locale);

        if ($form->isSubmitted())
        {
            $error = 1;
            $message = "";

            if($request->isXmlHttpRequest())
            {
                $contact = $form->getData();
                $errors = $validator->validate($contact);
    
                if (count($errors) > 0) 
                {
                    $message = (string) $errors[0]->getMessage();
                }
                else
                {
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($contact);
                    $entityManager->flush();

                    $mailer->sendMailAddContact($contact);

                    $error = 0;
                    $message = $translator->trans('contact.addContact.send.ok');
                }
            }
            else
            {
                $message = $translator->trans('contact.addContact.notAjax');
            }

            return new JsonResponse(array(
                'error' => $error,
                'message' => $message),
                200);
        }

        return $this->render('web_site/contact.html.twig', array(
          'form' => $form->createView(),
          "language" => $language
        ));
    }
}
