<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Event;
use App\Entity\Attendee;

/**
 * @Route("/attendee")
 */
class AttendeeController extends AbstractController
{
    /**
     * @Route("/", name="attendee")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AttendeeController.php',
        ]);
    }

    /**
     * @Route("/new", name="newAttendee")
     */
    public function newAttendee(Request $request): Response
    {
        $eventID = $request->query->get("eventID"); 
        
        echo(' - EventID en newAttendee: '.$eventID);

        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->find($eventID);

        return $this->render('app/private/attendee/new.html.twig',[
            'event' => $event,
        ]);
    }

    /**
     * @Route("/create", name="createAttendee")
     */
    public function create(Request $request): Response
    {
        $eventID = $request->query->get("eventID"); 
        $firstName = $request->query->get("firstName"); 
        $lastName = $request->query->get("lastName"); 
        $email = $request->query->get("email"); 
        $dni = $request->query->get("dni"); 
        $cond = $request->query->get("cond"); 

echo(' - eventID: '.$eventID);
echo(' - firstName: '.$firstName);
echo(' - lastName: '.$lastName);
echo(' - Email: '.$email);
echo(' - DNI: '.$dni);
echo(' - Condición: '.$cond);

        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->find($eventID);
       
        $attendee = $this->getDoctrine()
            ->getRepository(Attendee::class)
            ->findOneBy([
                'dni' => $dni
            ]);
        
        $em = $this->getDoctrine()->getManager();

        if ($attendee != null) {
            $attendee->setFirstName($firstName)
                ->setLastName($lastName)
                ->setEmail($email)
                ->setCond($cond);
            $em->flush();
        }
        else {
            $attendee = new Attendee();
            $attendee->setFirstName($firstName)
                ->setLastName($lastName)
                ->setEmail($email)
                ->setDni($dni)
                ->setCond($cond);
            $em->persist($attendee);
            $em->flush();
        }
        echo('  -  Nombre del evento: '.$event->getName());
        echo('  -  ID del evento: '.$event->getId());
        
        $event->addAttendee($attendee);

        return new Response ('El asistente se cargó correctamente.');
    }
}
