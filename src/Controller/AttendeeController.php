<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Event;
use App\Entity\Attendee;
use App\Entity\EventAttendee;
use App\Repository\EventRepository;
use App\Repository\AttendeeRepository;
use App\Repository\EventAttendeeRepository;
use App\Entity\ValidateController;

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

        if ($firstName == '' or $lastName == '' or $dni == '' or $email == '' or $cond == '') {
            $this->addFlash("error", "Deben completarse todos los datos del asistente.");
            
            return $this->redirectToRoute('newAttendee',[
                'eventID' => $eventID,
            ]);  
        }

        $response = $this->forward('App\Controller\ValidateController::validateAttendeeData',[
            'firstName' => $firstName,
            'lastName' => $lastName,
            'dni' => $dni,
            'email' => $email,
            'cond' => $cond,
        ]);

        if ($response->getContent() > 0) {
            
            return $this->redirectToRoute('newAttendee',[
                'eventID' => $eventID,
            ]);
        }
        else {

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

    /**
     * @Route("/view", name="viewEventAttendee");
     */
    public function viewEventAttendee(Request $request): Response
    {
        $eventAttendeeID = $request->query->get("eventAttendeeID"); 
        
        $eventAttendee = $this->getDoctrine()
            ->getRepository(EventAttendee::class)
            ->find($eventAttendeeID);
       
        return $this->render('app/private/eventAttendee/detail.html.twig',[
            'eventAttendee' => $eventAttendee,
        ]);
    }

    /**
     * @Route("/delete", name="deleteEventAttendee")
     */
    public function deleteEventAttendee(Request $request): Response
    {   
        $eventAttendeeID = $request->query->get("eventAttendeeID"); 
        echo('   $eventAttendeeID: '.$eventAttendeeID);
        $eventAttendee = $this->getDoctrine()
            ->getRepository(EventAttendee::class)
            ->find($eventAttendeeID);
        
        $event = $eventAttendee->getEvent();
        $eventName = $event->getName();
        $cityName = $eventAttendee->getEvent()->getCity()->getName();
        $attendeeFirstName = $eventAttendee->getAttendee()->getFirstName();
        $attendeeLastName = $eventAttendee->getAttendee()->getLastName();

        $em = $this->getDoctrine()->getManager();
        $em->remove($eventAttendee);
        $em->flush();

        $this->addFlash("success", "El asistente $attendeeFirstName $attendeeLastName ha sido removido del evento $eventName, $cityName.");
       
        return $this->redirectToRoute('viewAttendees',[
            'eventID' => $event->getId(),
        ]);  
    }

    /**
     * @Route("/modify", name="modifyEventAttendee")
     */
    public function modifyEventAttendee(Request $request): Response
    {
        $eventAttendeeID = $request->query->get("eventAttendeeID"); 
        
        $eventAttendee = $this->getDoctrine()
            ->getRepository(EventAttendee::class)
            ->find($eventAttendeeID);
        
        $firstName = $request->query->get("attendeeFirstName");
        $lastName = $request->query->get("attendeeLastName");
        $dni = $request->query->get("attendeeDni");
        $email = $request->query->get("attendeeEmail");
        $cond = $request->query->get("attendeeCond");
        

        if ($firstName == '' or $lastName == '' or $dni == '' or $email == '' or $cond == '') {
            $this->addFlash("error", "Deben completarse todos los datos del asistente.");
            
            return $this->redirectToRoute('viewEventAttendee',[
                'eventAttendeeID' => $eventAttendee->getId(),
            ]);  
        }

        if ($dni != $eventAttendee->getAttendee()->getDni()) {
            $this->addFlash("error", "El DNI del asistente no puede ser modificado.");
            return $this->redirectToRoute('viewEventAttendee',[
                'eventAttendeeID' => $eventAttendee->getId(),
            ]); 
        }

        $response = $this->forward('App\Controller\ValidateController::validateAttendeeData',[
            'firstName' => $firstName,
            'lastName' => $lastName,
            'dni' => $dni,
            'email' => $email,
            'cond' => $cond,
        ]);

        if ($response->getContent() > 0) {
            
            return $this->redirectToRoute('viewEventAttendee',[
                'eventAttendeeID' => $eventAttendee->getId()
            ]);
        }
        else {

            $originalFirstName = $eventAttendee->getAttendee()->getFirstName();
            $originalLastName = $eventAttendee->getAttendee()->getLastName();
            $originalEmail = $eventAttendee->getEmail();
            $originalCond = $eventAttendee->getCond();

            $em = $this->getDoctrine()->getManager();

            $eventAttendee->setEmail($email)
                ->setCond($cond);

            $attendee = $eventAttendee->getAttendee();
            $attendee->setFirstName($firstName)
                ->setLastName($lastName);

            $em->flush();
    
            if ($firstName != $originalFirstName or $lastName != $originalLastName or $email != $originalEmail or $cond != $originalCond) {
                $this->addFlash("success", "El asistente con dni $dni ha sido modificado con éxito.");
            }

            return $this->redirectToRoute('viewEvent',[
                'eventID' => $eventAttendee->getEvent()->getId(),
            ]); 
        }
    }

    /**
     * @Route("/fullSearchAttendees", name="fullSearchAttendees")
     */
    public function fullSearchAttendees(Request $request)
    {
        $toSearch = $request->query->get("toSearch"); 
        $eventID = $request->query->get("eventID");

        if ($eventID == '') {
            $this->addFlash("error", "El ID del evento no puede ser modificado A.");
            return $this->redirectToRoute('events');
        }
        else {
            $event = $this->getDoctrine()
                ->getRepository(Event::class)
                ->find($eventID);

            if ($event == null) {
                $this->addFlash("error", "El ID del evento no puede ser modificado B.");
                return $this->redirectToRoute('events');
            }
        }

        $em = $this->getDoctrine()->getManager();

        $qb = $em->createQueryBuilder('ea');
        
        $eventAttendees = $qb->select('ea')
            ->from('App\Entity\EventAttendee', 'ea')
            ->where('ea.email LIKE ?1')
            ->orWhere('ea.cond LIKE ?1')
            ->orWhere('a.first_name LIKE ?1')
            ->orWhere('a.last_name LIKE ?1')
            ->orWhere('a.dni LIKE ?1')
            ->andWhere('ea.event = ?2')
            ->innerJoin(
                Attendee::class, 'a', 'WITH', 'ea.attendee = a.id')
            ->setParameter(1, '%'.$toSearch.'%')
            ->setParameter(2, $eventID)
            ->getQuery()
            ->execute();

        return $this->render('app/private/attendee/index.html.twig',[
            'eventAttendees' => $eventAttendees,
            'event' => $event,
        ]);
    }

}