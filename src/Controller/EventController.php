<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Attendee;
use App\Repository\EventRepository;
use App\Entity\City;
use App\Entity\EventAttendee;
use App\Entity\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/event")
 */
class EventController extends AbstractController
{
    /**
     * @Route("/", name="events", methods={"GET"})
     */
    public function Index(EventRepository $eventRepository): Response
    {
        return $this->render('app/private/event/index.html.twig',[
            'events' => $eventRepository->findAll(),
        ]);
    }
    /**
     * @Route("/new", name="newEvent")
     */
    public function newEvent(): Response
    {
        $cities = $this->getDoctrine()
            ->getRepository(City::class)
            ->findAll();

        $templates = $this->getDoctrine()
            ->getRepository(Template::class)
            ->findAll();

        return $this->render('app/private/event/new.html.twig',[
            'templates' => $templates,
            'cities' => $cities,
        ]);
    }

    /**
     * @Route("/create", name="createEvent")
     */
    public function create(Request $request)
    {
        $eventName = $request->query->get("eventName");  
        $cityID = $request->query->get("cityID");  
        $startDateString = $request->query->get("startDate");  
        $endDateString = $request->query->get("endDate");  
        $templateID = $request->query->get("templateID");  
        $published = $request->query->get("published");  
        $CSVFile = $request->query->get("inputAttendeesFile");
        
        if (is_null($eventName) or is_null($cityID)) {
        
            $this->addFlash("error", "Ni el nombre del evento ni el nombre de la ubicación pueden estar vacíos.");
                        
            return $this->render('app/private/event/new.html.twig',[]);
        }

        $em = $this->getDoctrine()->getManager();

        $city = $this->getDoctrine()
        ->getRepository(City::class)
        ->find($cityID);

        if ($templateID != '') {
                    
            $template = $this->getDoctrine()
                ->getRepository(Template::class)
                ->find($templateID);
        }
        else {
            $template = null;
        }

        if ($startDateString != '') {
            $startDate = \DateTime::createFromFormat('Y-m-d', $startDateString);
        }
        else {
            $startDate = null;
        }
        if ($endDateString != '') {            
            $endDate = \DateTime::createFromFormat('Y-m-d', $endDateString);
        }
        else {
            $endDate = null;
        }
        
        if ($published) {            
            $published = 1;
        }
        else {
            $published = 0;
        }

        $duplicated = 0;

        if (($startDate != null) and ($endDate != null)) {  // si tengo nombre, ciudad y las dos fechas busco duplicados

            if ($endDate < $startDate) {

                $this->addFlash("error", "La fecha de finalización no puede ser anterior a la fecha de inicio.");        
                
                return $this->redirectToRoute('newEvent');
            }
            
            $duplicated = $this->getDoctrine()               // busco otro evento con los datos ingresados
                ->getRepository(Event::class)
                ->findDuplicated(0, $eventName, $city, $startDate, $endDate);
        }
        if ($duplicated == 0) {
                
            $event = new Event();
            $event->setName($eventName)
                ->setCity($city)
                ->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setTemplate($template)
                ->setPublished($published);
            $em->persist($event);
            $em->flush();

            if ($CSVFile != '') {
                $this->updateAttendees($event, $CSVFile);
            }

            $events = $this->getDoctrine()
                ->getRepository(Event::class)
                ->findAll(); 
            
            $cityName = $city->getName();
            $this->addFlash("success", "Evento '$eventName - $cityName' creado con éxito.");
            
            return $this->render('app/private/event/index.html.twig',[
                'events' => $events,
            ]);  
        }
        else {
            $cityName = $city->getName();
            $this->addFlash("error", "Ya existe otro evento con nombre $eventName realizado en $cityName desde el $startDateString hasta el $endDateString.");
        
            return $this->redirectToRoute('newEvent');
        }
    }  

    /**
     * @Route("/modify", name="modifyEvent")
     */
    public function modify(Request $request)
    {
        $eventID = $request->query->get("eventID");  
        $eventName = $request->query->get("eventName");  
        $cityID =  $request->query->get("cityID");  
        $startDateString =  $request->query->get("startDate");  
        $endDateString =  $request->query->get("endDate");  
        $templateID =  $request->query->get("templateID");  
        $published = $request->query->get("published");
        $CSVFile = $request->query->get("inputAttendeesFile");

        if ($published) {
            $published = 1;
        }
        else {
            $published = 0;
        }

        if ($eventName == '' or $cityID == '') {

            $this->addFlash("error", "Ni el nombre del evento ni el nombre de la ubicación pueden estar vacíos.");

            return $this->redirectToRoute('events');
        }
        
        $event = $this->getDoctrine()
        ->getRepository(Event::class)
        ->find($eventID);
      
        $em = $this->getDoctrine()->getManager();

        $city = $this->getDoctrine()
        ->getRepository(City::class)
        ->find($cityID);

        if ($templateID != '') {
                    
            $template = $this->getDoctrine()
                ->getRepository(Template::class)
                ->find($templateID);
        }
        else {
            $template = null;
        }

        if ($startDateString != '') {
            $startDate = \DateTime::createFromFormat('Y-m-d', $startDateString);
        }
        else {
            $startDate = null;
        }
        if ($endDateString != '') {            
            $endDate = \DateTime::createFromFormat('Y-m-d', $endDateString);
        }
        else {
            $endDate = null;
        }

        $duplicated = 0;

        if (($startDate != null) and ($endDate != null)) {  // si tengo nombre, ciudad y las dos fechas busco duplicados

            if ($endDate < $startDate) {
        
                $this->addFlash("error", "La fecha de finalización no puede ser anterior a la fecha de inicio.");        
                
                return $this->redirectToRoute('viewEvent',['eventID' => $event->getId()]);
            }

            $em = $this->getDoctrine()->getManager();

            $duplicated = $this->getDoctrine()
                ->getRepository(Event::class)
                ->findDuplicated($eventID, $eventName, $city, $startDate, $endDate);
        }
        if ($duplicated == 0) {
            
            $event->setName($eventName)
                ->setCity($city)
                ->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setTemplate($template)
                ->setPublished($published);
            $em->persist($event);
            $em->flush();

            $result = 0;
            
            if ($CSVFile != '') {
                $response = $this->updateAttendees($event, $CSVFile);
                $result = $response->getContent();
            }
            
            if ($result == 0) {
                $this->addFlash("success", 'Evento modificado con éxito!');
                return $this->redirectToRoute('events');           
            }
            else {
                $this->addFlash("error", 'El evento no se modificó correctamente.');
                return $this->redirectToRoute('viewEvent',['eventID' => $event->getId()]);
            }
        }
        else {
            $cityName = $city->getName();
            $this->addFlash("error", "Ya existe otro evento con nombre $eventName realizado en $cityName desde el $startDateString hasta el $endDateString.");
                            
            return $this->redirectToRoute('viewEvent',['eventID' => $event->getId()]);
        }       
         
    }

    /**
     * @Route("/fullSearch", name="fullSearchEvents")
     */
    public function fullSearchEvents(Request $request)
    {
        $toSearch = $request->query->get("toSearch"); 

        $response = $this->forward('App\Controller\ValidateController::validateDateAsString', [
            'dateString'  => $toSearch,
        ]);
        
        if (($response->getContent()) == 0) {
            $date = date("Y-m-d", strToTime($toSearch));
        }
        else {
            $date = date("Y-m-d", strToTime("1900-01-01"));
        }

        $em = $this->getDoctrine()->getManager();

        $qb = $em->createQueryBuilder('e');
        
        $events = $qb->select('e')
            ->from('App\Entity\Event', 'e')
            ->where('e.name LIKE ?1')
            ->orWhere('c.name LIKE ?1')
            ->orWhere('e.startDate = ?2')
            ->orWhere('e.endDate = ?2')
            ->innerJoin(
                City::class, 'c', 'WITH', 'e.city = c.id')
            ->setParameter(1, '%'.$toSearch.'%')
            ->setParameter(2, $date)
            ->getQuery()
            ->execute();

        return $this->render('app/private/event/index.html.twig',[
            'events' => $events,
        ]);
    }

    /**
     * @Route("/delete", name="deleteEvent")
     */
    public function deleteEvent(Request $request)
    {
        $eventID = $request->query->get("eventID"); 

        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->find($eventID);

        $em = $this->getDoctrine()->getManager();
        $em->remove($event);
        $em->flush();

        $events = $this->getDoctrine()
            ->getRepository(Event::class)
            ->findAll();
        
        return $this->render('app/private/event/index.html.twig',[
            'events' => $events,
        ]);
    }

    /**
     * @Route("/view", name="viewEvent")
     */
    public function viewEvent(Request $request)
    {
        $eventID = $request->query->get("eventID"); 

        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->find($eventID);
        
        $cities = $this->getDoctrine()
            ->getRepository(City::class)
            ->findAll();
        
        $templates = $this->getDoctrine()
            ->getRepository(Template::class)
            ->findAll();

        $attendeesQty = count($event->getEventAttendees());
        
        return $this->render('app/private/event/modify.html.twig',[
            'event' => $event,
            'cities' => $cities,
            'templates' => $templates,
            'qty' => $attendeesQty,
        ]);
    }
    
    /**
     * @Route("/updateAttendees", name="updateAttendees")
     */
    public function updateAttendees(Event $event, string $CSVFile)
    {           
        if (($file = fopen("../".$CSVFile, "r")) !== FALSE) {

            $em = $this->getDoctrine()->getManager();

            while (($data = fgetcsv($file, 0, ",")) !== FALSE) {

                $errors = [];
                /*$response = $this->forward('App\Controller\ValidateController::validateAttendeesData', [
                    'data'  => $data,
                ]); */
                
                if ($data[1] == '' or $data[2] == '' or $data[3] == '' or $data[4] == '' or $data[5] == '') {
                    $errors[] = 'Existen campos vacíos en la planilla importada.';
                }

                $responseLN = $this->forward('App\Controller\ValidateController::validateName', [
                    'name'  => $data[1],
                ]);

                if (($responseLN->getContent()) != 0) {
                    $errors[] = 'El apellido del asistente con DNI '.$data[4].' es incorrecto.';
                }

                $responseFN = $this->forward('App\Controller\ValidateController::validateName', [
                    'name'  => $data[2],
                ]);

                if (($responseFN->getContent()) != 0) {
                    $errors[] = 'El nombre del asistente con DNI '.$data[4].' es incorrecto.';
                }

                $responseE = $this->forward('App\Controller\ValidateController::validateEmail', [
                    'email'  => $data[3],
                ]);

                if (($responseE->getContent()) != 0) {
                    $errors[] = 'El email del asistente con DNI '.$data[4].' es incorrecto.';
                }

                $responseD = $this->forward('App\Controller\ValidateController::validateDni', [
                    'dni'  => $data[4],
                ]);

                if (($responseD->getContent()) != 0) {
                    $errors[] = 'El DNI del asistente '.$data[1].', '.$data[2].' es incorrecto.';
                }

                $responseC = $this->forward('App\Controller\ValidateController::validateName', [
                    'name'  => $data[5],
                ]);

                if (($responseC->getContent()) != 0) {
                    $errors[] = 'La condición del asistente con DNI '.$data[4].' es incorrecta.';
                }

                if (count($errors) > 0) {

                    $this->addFlash("error", "El archivo de asistentes no pudo procesarse correctamente. Corrija los siguientes errores y vuelva a procesarlo.");
                    
                    foreach ($errors as $error) {
                        $this->addFlash("error", $error);
                    }
                    return new Response (1);
                }
                else {
                    $attendee = $this->getDoctrine()
                        ->getRepository(Attendee::class)
                        ->findOneBy(['dni' => $data[4]]);

                    if ($attendee != null) {
                        $attendee->setLastName($data[1])
                            ->setFirstName($data[2]);
                        $em->flush();
                    }
                    else{        
                        $attendee = new Attendee();
                        $attendee->setFirstName($data[1])
                            ->setLastname($data[2])
                            ->setDni($data[4]);
                            
                        $em->persist($attendee);
                        $em->flush();
                    }
                    $newEventAttendee = $this->getDoctrine()
                        ->getRepository(EventAttendee::class)
                        ->findOneBy([
                            'event' => $event,
                            'attendee' => $attendee,
                        ]);
                    
                    if ($newEventAttendee == null) {               //si el asistente no está registrado en este evento
                        $newEventAttendee = new EventAttendee();
                        $newEventAttendee->setEvent($event)
                            ->setAttendee($attendee)
                            ->setEmail($data[3])
                            ->setCond($data[5]);
                        $em->persist($newEventAttendee);
                        $em->flush();
                    }
                    else {
                        $newEventAttendee->setEmail($data[3])
                            ->setCond($data[5]);
                        $em->flush();
                    }
                                                            
                    $event->addEventAttendee($newEventAttendee);
                }
                $em->flush();
            }
            fclose($file);
            
            $this->addFlash("success", "El archivo de asistentes se procesó correctamente.");
        }
        
        return new Response (0);
    }

    /**
     * @Route("/viewAttendees", name="viewAttendees")
     */
    public function viewAttendees(Request $request)
    {
        $eventID = $request->query->get("eventID");
    
        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->find($eventID);
    
        $eventAttendees = $this->getDoctrine()
            ->getRepository(EventAttendee::class)
            ->findBy([
                'event' => $event,
            ]);
    
        return $this->render('app/private/attendee/index.html.twig',[
            'event' => $event,
            'eventAttendees' => $eventAttendees,
        ]);
    }
}