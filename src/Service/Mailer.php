<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Entity\Contact;
use App\Entity\Name;

use App\Service\Payments;

class Mailer
{
    private $mailer;
    private $addresses;
    private $tools;
    private $webSiteName;
    private $translator;

    public function __construct(MailerInterface $mailer, array $addresses, Tools $tools, string $webSiteName, TranslatorInterface $translator)
    {
        $this->mailer = $mailer;
        $this->addresses = $addresses;
        $this->tools = $tools;
        $this->webSiteName = $webSiteName;
        $this->translator = $translator;
    }

    public function sendMail($from, $to, $subject, $msg_html, $msg_txt)
    {
        $email = (new Email())
            ->from(new Address($from, $this->webSiteName))
            ->to($to)
            ->subject($subject)
            ->text($msg_txt)
            ->html($msg_html);

        return $this->mailer->send($email);
    }

    public function sendTemplatedMail($from, $to, $subject, $templateFile, $datas)
    {
        $email = (new TemplatedEmail())
        ->from(new Address($from, $this->webSiteName))
        ->to($to)
        ->subject($subject)
        ->htmlTemplate('emails/'.$templateFile.'.html.twig')
        ->context($datas);

        return $this->mailer->send($email);
    }

    public function sendMailAddContact(Contact $contact)
    {
        $subject = "Quelqu'un a envoyé une demande de contact";
        $message_html = "Demande de contact de : <br><br>".$contact->toEmail();
        $message_txt = str_replace("<br>", "\n", $message_html);

        $this->sendMail($this->addresses['noreply'], $this->addresses['contact'], $subject, $message_html, $message_txt);
    }

    public function sendMailErrorStripe(Name $name, $error)
    {
        $subject = $name->idForMail()." Probleme de paiment pour un nom par stripeCard";
        $message_html = "Un probleme est survenu lors du paiment par stripeCard 
                        (Le nom n'a pas été acheté, ni confirmé)(on est passé dans la catch de add_name)".
                        $name->toEmail().implode($error);
        $message_txt = str_replace("<br", "\n", $message_html);

        $this->sendMail($this->addresses['noreply'], $this->addresses['support'], $subject, $message_html, $message_txt);
    }

    public function sendMailErrorPayPal(Name $name, string $response)
    {
        $subject = $name->idForMail()." Probleme de création de l'ordreID par paypal paiement";
        $message_html = "Un probleme est survenu lors de la création de l'id de paiement paypal 
                        (le nom a été ajouté dans la base de donnée en tout cas)".
                        $name->toEmail()."<br>".json_encode($response, JSON_PRETTY_PRINT);
        $message_txt = str_replace("<br", "\n", $message_html);

        $this->sendMail($this->addresses['noreply'], $this->addresses['support'], $subject, $message_html, $message_txt);
    }

    public function sendConfirmPayment(Name $name, string $event, $paymentIntent = null)
    {
        $buyerLanguage = $name->getOrder()->getLanguage();

        /*Beginning send mail to confirm that the name was added to the buyer */
        $confirmedDate = $name->getConfirmationDate()->format($this->translator->trans('mail.addName.body.addedDate', array(),'messages', $buyerLanguage));
       
        $subject = $this->translator->trans('mail.addName.subject', array(),'messages', $buyerLanguage); 

        $datas = array("name" => $name,
                        "confirmedDate" => $confirmedDate,
                        "addressContact" => $this->addresses['contact'],
                        "buyerLanguage" => $buyerLanguage
                    );

        $this->sendTemplatedMail($this->addresses['noreply'], $name->getOrder()->getBuyerEmail(), $subject, 'add_name', $datas);
        /* End of the send mail */

        /* beginning of the send the receipt to the buyer */
        $subject = $this->translator->trans('mail.receipt.subject', ["%webSiteName%" => $this->webSiteName], 'messages', $buyerLanguage);

        if($paymentIntent)
        {
            $paymentMethod = $paymentIntent->charges->data[0]->payment_method_details->card->brand;
            $last4Numbers = $paymentIntent->charges->data[0]->payment_method_details->card->last4;
        }
        else{
            $paymentMethod = "";
            $last4Numbers = "PayPal";
        }

        $datas = array("name" => $name,
                "confirmedDate" => $confirmedDate,
                "paymentMethod" => $paymentMethod,
                "last4Numbers" => $last4Numbers,
                "addressContact" => $this->addresses['contact'],
                "buyerLanguage" => $buyerLanguage
            );

        $this->sendTemplatedMail($this->addresses['noreply'], $name->getOrder()->getBuyerEmail(), $subject, 'receipt', $datas);
        /*END of seding the receipt*/

        $method = $name->getOrder()->getPaymentMethod()->getMethodName();

        $subject = $name->idForMail()." Un nom vient d'être confirmé par ".$method." (webhook event : ".$event.")";

        $message_html = "Un nouveau nom vient d'être confirmé par ".$method." webhook : ".$name->toEmail();
        $message_txt = str_replace("<br", "\n", $message_html);
        
        $this->sendMail($this->addresses['noreply'], $this->addresses['support'], $subject, $message_html, $message_txt);
    }

    public function sendCancelPayment(Name $name, string $event, $paymentIntent = "")
    {
        $method = $name->getOrder()->getPaymentMethod()->getMethodName();

		$subject = $name->idForMail()." Probleme de paiment pour un nom par ".$method." (webhook event : ".$event.")";
        $message_html = "Erreur lors du paiement, (envoi à partir de ".$method." webhook) (Le nom n'a pas été acheté)".
                        $name->toEmail()."<br><br>PaymentIntent:<pre>".$paymentIntent."</pre>";
        $message_txt = str_replace("<br", "\n", $message_html);
        
        $this->sendMail($this->addresses['noreply'], $this->addresses['support'], $subject, $message_html, $message_txt);
    }

    public function sendRefundPayment(Name $name, $event, $paymentIntent)
    {
        $buyerLanguage = $name->getOrder()->getLanguage();
        $reason = $name->getOrder()->getRefundReason();

		$subject = $this->translator->trans('mail.deleteName.subject', array(),'messages', $buyerLanguage); 

        $datas = array("reason" => $reason,
                        "name" => $name,
                        "addressContact" => $this->addresses['contact'],
                        "buyerLanguage" => $buyerLanguage
                    );

        $this->sendTemplatedMail($this->addresses['noreply'], $name->getOrder()->getBuyerEmail(), $subject, 'delete_name', $datas);

        $method = $name->getOrder()->getPaymentMethod()->getMethodName();

		$subject = $name->idForMail()." Remboursement par ".$method." (webhook event : ".$event.")";
        $message_html = "Remboursement a été effectué correctement, (envoi à partir de ".$method." webhook)".
                        $name->toEmail()."<br><br>PaymentIntent:<pre>".$paymentIntent."</pre>";
        $message_txt = str_replace("<br", "\n", $message_html);
        
        $this->sendMail($this->addresses['noreply'], $this->addresses['support'], $subject, $message_html, $message_txt);
    }

    public function sendAlertPayment(string $method, $event, $paymentIntent)
    {
        $subject = " !!! Alerte mail tres important (".$method." webhook event : ".$event.")";
        $message_html = "Evenement de fraude ou de dispute pour un achat avec ".$method." (envoi à partir de ".$method." webhook)
                        <br><br><pre>".$paymentIntent."</pre>";
		$message_txt = str_replace("<br", "\n", $message_html);

        $this->sendMail($this->addresses['noreply'], $this->addresses['support'], $subject, $message_html, $message_txt);
    }

    public function sendErrorPurgeName(Name $name)
    {
        $subject = $name->idForMail()." !!! Une erreur est survenue lors de la suppression d'un nom lors de la purge";
        $message_html = "Probleme de suppression de nom lors de la purge pour le nom : <br><br>".$name->toEmail();
		$message_txt = str_replace("<br", "\n", $message_html);

        $this->sendMail($this->addresses['noreply'], $this->addresses['cron'], $subject, $message_html, $message_txt);
    }
}