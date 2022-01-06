<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use Knp\Snappy\Pdf;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;

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
        $attendee = $this->getDoctrine()
            ->getRepository(Attendee::class)
            ->find(1);
        
        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->find(1);

        return $this->render('app/private/certificate/certificate.html.twig', [
            'attendee'  => $attendee,
            'event' => $event,
        ]);
    }

    public function pdfAction(Attendee $attendee, Event $event)
    {
        $knpSnappyPdf = new Pdf('/usr/bin/wkhtmltopdf');
        $knpSnappyPdf->setOption('lowquality', false);
        $knpSnappyPdf->setOption('disable-javascript', true);
        $knpSnappyPdf->setOption('page-size', 'A4');
        $knpSnappyPdf->setOption('orientation', 'Landscape');
        $knpSnappyPdf->setOption('enable-local-file-access', true);
        $knpSnappyPdf->setOption('images', true);
        $knpSnappyPdf->setOption('margin-bottom', 0);
        $knpSnappyPdf->setOption('margin-left', 0);
        $knpSnappyPdf->setOption('margin-right', 0);
        $knpSnappyPdf->setOption('margin-top', 0);
        
        $html = $this->renderView('app/private/certificate/certificate.html.twig', [
            'attendee'  => $attendee,
            'event' => $event,
        ]);

        # return $knpSnappyPdf->getOutputFromHtml($html);

        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html),
            'file.pdf',
            array(
                'images' =>true,            
            )
        );
    }
    
    /**
     * @Route("/certificados/{attendeeID}/{eventID}/{destinationEmail}", name="sendEmail")
     */
    public function sendEmail(Request $request, MailerInterface $mailer, string $attendeeID, int $eventID, string $destinationEmail)
    {
        $attendee = $this->getDoctrine()
            ->getRepository(Attendee::class)
            ->find($attendeeID);
        
        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->find($eventID);

        $pdfFile = $this->pdfAction($attendee, $event);
        
        $filename = $attendee->getLastName() . $attendee->getFirstName() . '-' . $event->getName() . '.pdf';

        $email = (new Email())
            ->from('lauchaoleastro3@gmail.com')
            ->to($destinationEmail)
            ->subject('Certificado - ' . $event->getName())
            ->text('Este mensaje ha sido generado automáticamente. Por favor, no responder.')
            ->attach($pdfFile, $filename);

        

        # $mailer->send($email);
        
        $this->addFlash("success", "Certificado enviado correctamente al mail: " . $destinationEmail );
        
        # return $this->redirectToRoute('public', [ 'dni' => $attendee->getDni() ]);

        return $pdfFile;
    }
}
