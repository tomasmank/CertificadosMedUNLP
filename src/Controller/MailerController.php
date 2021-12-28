<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Knp\Snappy\Pdf;

use App\Repository\AttendeeRepository;
use App\Entity\Attendee;
use App\Repository\EventRepository;
use App\Entity\Event;

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

    public function indexAction(Pdf $snappy)
    {
        $html = $this->renderView('index.html.twig');

        return $snappy->getOutputFromHtml($html);
    }
    
    /**
     * @Route("/email/{attendeeID}/{eventID}", name="sendEmail")
     */
    public function sendEmail(Request $request, MailerInterface $mailer, string $attendeeID, int $eventID)
    {
        $attendee = $this->getDoctrine()
            ->getRepository(Attendee::class)
            ->find($attendeeID);
        
        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->find($eventID);

        # $snappy = new Pdf('/usr/bin/wkhtmltopdf');
        # $pdfFile = $this->indexAction($snappy);
        $email = (new Email())
            ->from('lauchaoleastro3@gmail.com')
            ->to($attendee->getEmail())
            ->subject('Certificado - ' . $event->getName())
            ->text('Este mensaje ha sido generado automáticamente. Por favor, no responder.');
            
        # $mailer->send($email);

        $this->addFlash("success", "Certificado enviado correctamente al mail: " . $attendee->getEmail() );
        
        return $this->redirectToRoute('public', [ 'dni' => $attendee->getDni() ]);
    }
}
