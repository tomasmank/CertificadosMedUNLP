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
use App\Repository\EventAttendeeRepository;
use App\Entity\EventAttendee;

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

    private function replaceVariables(Attendee $attendee, Event $event, EventAttendee $attendance): String
    {
        $searchValues = [
            '$asistente-nombre',
            '$asistente-apellido',
            '$asistente-dni',
            '$asistente-condicion',
            '$evento-nombre',
            '$evento-inicio',
            '$evento-fin',
            '$evento-ciudad'
        ];

        $replaceValues = [
            $attendee->getFirstName(),
            $attendee->getLastName(),
            $attendee->getDni(),
            $attendance->getCond(),
            $event->getName(),
            $event->getStartDate()->format('d, M, y'),
            $event->getEndDate()->format('d, M, y'),
            $event->getCity()->getName()
        ];

        $text = $event->getTemplate()->getBody();

        return str_replace($searchValues, $replaceValues, $text);
    }

    public function pdfAction(Attendee $attendee, Event $event, EventAttendee $attendance)
    {
        $knpSnappyPdf = new Pdf('/usr/local/bin/wkhtmltopdf');
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
            'body' => $this->replaceVariables($attendee, $event, $attendance)
        ]);

        # des-comentar para enviar mail
        # return $knpSnappyPdf->getOutputFromHtml($html);

        # se convierte a ASCII porque PdfResponse no acepta $filename con caracteres especiales
        # dice ser un bug de la clase PdfResponse / Response,
        # por lo que posiblemente no sea necesario  usar esto en el envio de mail
        $filename = mb_convert_encoding($attendee->getLastName() . $attendee->getFirstName() . '-' . $event->getName() . '.pdf', "ASCII");

        return new PdfResponse(
            $knpSnappyPdf->getOutputFromHtml($html),
            $filename,
            array(
                'images' =>true,            
            )
        );
    }
    
    /**
     * @Route("/certificados/{attendeeID}/{eventID}/{attendanceID}", name="sendEmail")
     */
    public function sendEmail(Request $request, MailerInterface $mailer, string $attendeeID, int $eventID, string $attendanceID)
    {
        $attendee = $this->getDoctrine()
            ->getRepository(Attendee::class)
            ->find($attendeeID);
        
        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->find($eventID);

        $attendance = $this->getDoctrine()
            ->getRepository(EventAttendee::class)
            ->find($attendanceID);

        $pdfFile = $this->pdfAction($attendee, $event, $attendance);
        
        $filename = $attendee->getLastName() . $attendee->getFirstName() . '-' . $event->getName() . '.pdf';

        $destinationEmail = $attendance->getEmail();

        # sacar comentarios para enviar mail

        #$email = (new Email())
        #    ->from('lauchaoleastro3@gmail.com')
        #    ->to($destinationEmail)
        #    ->subject('Certificado - ' . $event->getName())
        #    ->text('Este mensaje ha sido generado automáticamente. Por favor, no responder.')
        #    ->attach($pdfFile, $filename);

        # $mailer->send($email);
        
        $this->addFlash("success", "Certificado enviado correctamente al mail: " . $destinationEmail );
        
        # return $this->redirectToRoute('public', [ 'dni' => $attendee->getDni() ]);

        return $pdfFile;
    }
}
