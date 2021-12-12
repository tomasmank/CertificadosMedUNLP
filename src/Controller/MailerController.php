<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Knp\Snappy\Pdf;

class MailerController extends AbstractController
{
    /**
     * @Route("/mailer", name="mailer")
     */
    public function index()
    {
        return $this->render('mailer/index.html.twig', [
            'controller_name' => 'MailerController',
        ]);
    }

    /**
     * @Route("/pdf", name="pdf")
     */
    public function indexAction(\Knp\Snappy\Pdf $snappy)
    {
        $html = $this->renderView('index.html.twig');

        return $snappy->getOutputFromHtml($html);
    }
    
    /**
     * @Route("/email/{sendTo}/{eventName}", name="send_email")
     */
    public function sendEmail(MailerInterface $mailer, string $sendTo, $eventName): Response
    {
        $snappy = new Pdf('/usr/bin/wkhtmltopdf');
        $pdfFile = $this->indexAction($snappy);
        $email = (new Email())
            ->from('backup.dario@gmail.com')
            ->to($sendTo)
            ->subject('Certificado de asistencia - Evento: '.$eventName)
            ->text('El archivo adjunto fue generado desde una vista HTML. El nombre del archivo y el nombre del evento que figura en el asunto se generaron a partir de variables. (Contenido: texto)')
     #       ->text('Adjuntamos al presente su certificado de asistencia al evento del asunto.')
            ->html('<p style="color:red;font-size:200%;"> El archivo adjunto fue generado desde una vista HTML y tanto su nombre como el nombre del evento que figura en el asunto se generaron a partir de variables.</p><br/><p style="color:blue;font-size:150%;">El texto en rojo y este texto están escritos en HTML/Twig.</p>')
     #       ->attachFromPath('../src/filesRepository/RCP diplomas.pdf');
            ->attach($pdfFile, 'Certificado '.$eventName.'.pdf');
            
        $mailer->send($email);

        return new Response('Su certificado fue enviado a la cuenta de correo '.$sendTo.'.');
    }
}
