<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Event;
use App\Entity\Attendee;
use App\Entity\EventAttendee;

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
                ->setLastName($lastName);
            $em->flush();
        }
        else {
            $attendee = new Attendee();
            $attendee->setFirstName($firstName)
                ->setLastName($lastName)
                ->setDni($dni);
            $em->persist($attendee);
            $em->flush();
        }
        
        $newEventAttendee = $this->getDoctrine()
            ->getRepository(EventAttendee::class)
            ->findOneBy([
                'event' => $event,
                'attendee' => $attendee,
            ]);
        
        if($newEventAttendee != null) {
            $newEventAttendee->setEmail($email)
                ->setCond($cond);
            $em->flush();
        }
        else {
            $newEventAttendee = new EventAttendee();
            $newEventAttendee->setEvent($event)
                ->setAttendee($attendee)
                ->setEmail($email)
                ->setCond($cond);
            $em->persist($newEventAttendee);
            $em->flush();
        }

        $event->addEventAttendee($newEventAttendee);

        return $this->redirectToRoute('viewAttendees',[
            'eventID' => $event->getId(),
        ]);;
    }
}
