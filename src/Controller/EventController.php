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
use App\Form\EventType;

/**
 * @Route("/event")
 */
class EventController extends AbstractController
{
    /**
     * @Route("/index/{currentPage}", name="events", methods={"GET"})
     */
    public function Index(EventRepository $eventRepository, Request $request, $currentPage = 1): Response
    {
    //    $this->denyAccessUnlessGranted('ROLE_EVENT_LIST');

        $perPage = 20;

        $toSearch = $request->query->get("toSearch"); 

        #$em = $this->getDoctrine()->getManager();
        
        #$qb = $em->createQueryBuilder('e');

        $events = $eventRepository->getAll($currentPage, $perPage, $toSearch);
        $eventsResult = $events['paginator'];
        $eventsFullQuery = $events['query'];

        $maxPages = ceil($events['paginator']->count() / $perPage);

        return $this->render('app/private/event/index.html.twig',[
            'events' => $eventsResult,
            'maxPages'=> $maxPages,
            'thisPage' => $currentPage,
            'all_items' => $eventsFullQuery,
            'searchParameter' => $toSearch
        ]);
    }
    
    /**
     * @Route("/view", name="viewEvent")
     */
    public function viewEvent(Request $request)
    {
        $action = $request->query->get("action");
        
        if ($action == 'new') {
            $textToShow = 'creado';
            $event = new Event();
            $eventID = 0;
        }
        else {
            $textToShow = 'modificado';
            $eventID = $request->query->get("eventID"); 
        
            $event = $this->getDoctrine()
                ->getRepository(Event::class)
                ->find($eventID);

            if ($event == null) {
                $this->addFlash("error", "El ID del evento no puede ser modificado.");
                return $this->redirectToRoute('events');
            }
        }

        $form = $this->createForm(EventType::class, $event);
                
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $event = $form->getData();

            $eventName = $event->getName();
            $city = $event->getCity();
            $published = $event->getPublished();
            $template = $event->getTemplate();
            $startDate = $event->getStartDate();
            $endDate = $event->getEndDate();

            /** @var UploadedFile $attendeeFile */
            $attendeeFile = $form->get('attendeeFile')->getData();
            $newFileName = '';
                    
            if ($attendeeFile) {
                $originalFileName = pathinfo($attendeeFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFileName = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFileName);
                $newFileName = $safeFileName.'-'.uniqid().'.'.$attendeeFile->guessExtension();
                
                try {
                    $attendeeFile->move(
                        $this->getParameter('attendees_directory'),
                        $newFileName
                    );
                    
                } catch (FileException $e) {
                    // manejar excepcion
                    return $this->redirectToRoute('viewEvent', [
                        'eventID' => $eventID,
                        'action' => $action,  
                    ]);  
                }
            }

            $response = $this->forward('App\Controller\ValidateController::validateEventData',[
                'eventName' => $eventName,
                'city' => $city,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'fileName' => $newFileName,
            ]);
            
            if ($response->getContent() > 0) { 
                
                return $this->redirectToRoute('viewEvent', [
                    'eventID' => $eventID,
                    'action' => $action,
                ]);
            }

            else {
                
                $em = $this->getDoctrine()->getManager();
                
                if ($published) {            
                    $published = 1;
                }
                else {
                    $published = 0;
                }

                $duplicated = 0;

                if (($startDate != null) and ($endDate != null)) {
                    $eventID = $event->getId();
                    if ($eventID == null) {                    
                        $eventID = 0;
                    }
                    $duplicated = $this->getDoctrine()            
                        ->getRepository(Event::class)
                        ->findDuplicated($eventID, $eventName, $city, $startDate, $endDate);
                }

                $cityName = $city->getName();

                if ($duplicated == 0) {
                    
                    if ($template == null and $published == 1) {
                        $published = 0;
                        $this->addFlash("error", "ATENCION: El evento '$eventName - $cityName' no fue publicado por no tener un template asignado.");
                    }

                    $event->setName($eventName)
                        ->setCity($city)
                        ->setStartDate($startDate)
                        ->setEndDate($endDate)
                        ->setTemplate($template)
                        ->setPublished($published);
                    if ($action == 'new') {
                        $em->persist($event);
                    }
                    $em->flush();

                    if ($newFileName != '') {
                        $response = $this->updateAttendees($event, $newFileName);
                        $result = $response->getContent();
                               
                        if ($result != 0) {
                            $this->addFlash("error", "El evento '$eventName - $cityName' no pudo ser $textToShow correctamente.");          
                            return $this->redirectToRoute('events');          
                        }
                    }
                    
                    $this->addFlash("success", "El evento '$eventName - $cityName' ha sido $textToShow con éxito.");          
                    return $this->redirectToRoute('events');  
                }
                else {
                    $start = $startDate->format('d-m-Y');
                    $end = $endDate->format('d-m-Y');
                    $this->addFlash("error", "Ya existe otro evento con nombre '$eventName' realizado en $cityName desde el $start hasta el $end.");
                    return $this->redirectToRoute('viewEvent', [
                        'eventID' => $eventID,
                        'action' => $action,
                    ]);
                }
            }
        }

        return $this->render('app/private/event/new.html.twig',[
            'form' => $form->createView(),
        ]);
    }


    /**
     * @Route("/updateAttendees", name="updateAttendees")
     */
    public function updateAttendees(Event $event, string $newFileName)
    {           
        $pathToUploadFiles = $this->getParameter('attendees_directory');
        
        if (($file = fopen($pathToUploadFiles.'/'.$newFileName, "r")) !== FALSE) {

            if (($data = fgetcsv($file, 0, ",")) !== FALSE) {
                
                if (count($data) < 5) {
                    
                    $this->addFlash("error", "El archivo de asistentes no contiene los datos requeridos (apellido, nombre, e-mail, documento y condición).");
                    
                    return new Response (1);
                }

                if (count($data) > 20) {
                    $this->addFlash("error", "El archivo de asistentes no puede tener más de 20 columnas.");
                    return new Response (1);
                }
                
                $columnsFound = 0;
                $i = 0;

                while ($i <= (count($data)-1) and $columnsFound < 5) {
                    
                    if ($data[$i] != '' and stripos("apellidos", $data[$i]) !== false) {
                        $ln = $i;
                        $columnsFound++;
                    }
                    elseif ($data[$i] != '' and stripos("nombres", $data[$i]) !== false) {
                        $fn = $i;
                        $columnsFound++;
                    }
                    elseif ($data[$i] != '' and stripos("documentos-dni", $data[$i]) !== false) {
                        $dni = $i;
                        $columnsFound++;
                    }
                    elseif ($data[$i] != '' and stripos("emails-e-mails-correo", $data[$i]) !== false) {
                        $email = $i;
                        $columnsFound++;
                    }
                    elseif ($data[$i] != '' and stripos("condicion-condición", $data[$i]) !== false) {
                        $cond = $i;
                        $columnsFound++;
                    }
                    $i++;
                }

                if ($columnsFound < 5) {
                    $this->addFlash("error", "El archivo seleccionado no contiene los encabezados correctos.");
                    return new Response(1);
                }       
            }
            else {
                $this->addFlash("error", "Error al abrir el archivo. Verifique que sea de tipo CSV y que contenga los campos requeridos.");
                    return new Response(1);
            }
            
            $em = $this->getDoctrine()->getManager();

            if (($data = fgetcsv($file, 0, ",")) === FALSE) {
                $this->addFlash("error", "El archivo seleccionado solo contiene los encabezamientos.");
                return new Response (1);
            }

            do {

                if ($data[$ln] == '' or $data[$fn] == '' or $data[$email] == '' or $data[$dni] == '' or $data[$cond] == '') {
                    $this->addFlash("error", "Existen campos vacíos en la planilla importada (pueden haberse procesado algunos asistentes).");
                    return new Response (1);
                }

                $response = $this->forward('App\Controller\ValidateController::validateAttendeeData',[
                    'lastName' => $data[$ln],
                    'firstName' => $data[$fn],
                    'email' => $data[$email],
                    'dni' => $data[$dni],
                    'cond' => $data[$cond],
                ]);
        
                if ($response->getContent() > 0) {   
                    $this->addFlash("error", "El archivo de asistentes no pudo procesarse correctamente.");
                    return new Response (1);
                }
                else {

                    $attendee = $this->getDoctrine()
                        ->getRepository(Attendee::class)
                        ->findOneBy(['dni' => $data[$dni]]);

                    if ($attendee != null) {
                        $attendee->setLastName($data[$ln])
                            ->setFirstName($data[$fn]);
                        $em->flush();
                    }
                    else{        
                        $attendee = new Attendee();
                        $attendee->setFirstName($data[$ln])
                            ->setLastname($data[$fn])
                            ->setDni($data[$dni]);
                            
                        $em->persist($attendee);
                        $em->flush();
                    }
                    $newEventAttendee = $this->getDoctrine()
                        ->getRepository(EventAttendee::class)
                        ->findOneBy([
                            'event' => $event,
                            'attendee' => $attendee,
                        ]);
                    
                    if ($newEventAttendee == null) {
                        $newEventAttendee = new EventAttendee();
                        $newEventAttendee->setEvent($event)
                            ->setAttendee($attendee)
                            ->setEmail($data[$email])
                            ->setCond($data[$cond]);
                            $iidd = $event->getId();
                            $aid = $attendee->getId();
                        $em->persist($newEventAttendee);
                        $em->flush();
                    }
                    else {
                        $newEventAttendee->setEmail($data[$email])
                            ->setCond($data[$cond]);
                        $em->flush();
                    }
                                                            
                    $event->addEventAttendee($newEventAttendee);
                }
                $em->flush();

            } while (($data = fgetcsv($file, 0, ",")) !== FALSE);

            fclose($file);
            $this->addFlash("success", "El archivo de asistentes ha sido procesado correctamente.");
            return new Response (0);
        }   
        else {
            $this->addFlash("error", "Error al abrir el archivo. Verifique que sea de tipo CSV y que contenga los campos requeridos.");
            return new Response(1);
        } 
    }
    
    /**
     * @Route("/viewAttendees/{currentPage}", name="viewAttendees", methods={"GET"})
     */
    public function viewAttendees(Request $request, $currentPage = 1)
    {
        $perPage = 50;

        $toSearch = $request->query->get("toSearch");

        $eventID = $request->query->get("eventID");
    
        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->find($eventID);
    
        $eventAttendees = $this->getDoctrine()
            ->getRepository(EventAttendee::class)
            ->getAttendees($event, $currentPage, $perPage, $toSearch);
        
        $eventAttendeesResult = $eventAttendees['paginator'];
        $eventAttendeesFullQuery = $eventAttendees['query'];
        $maxPages = ceil($eventAttendees['paginator']->count() / $perPage);

        return $this->render('app/private/attendee/index.html.twig',[
            'event' => $event,
            'eventAttendees' => $eventAttendeesResult,
            'maxPages'=> $maxPages,
            'thisPage' => $currentPage,
            'all_items' => $eventAttendeesFullQuery,
            'searchParameter' => $toSearch
        ]);
    }
    
    /**
     * @Route("/fullSearch", name="fullSearchEvents")
     */
    public function fullSearchEvents(Request $request)
    {
        $toSearch = null;
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
            ->orderBy('e.published', 'ASC')
            ->addOrderBy('e.name', 'ASC')
            ->getQuery()
            ->execute();

        return $this->render('app/private/event/index.html.twig',[
            'events' => $events,
            'searchParameter' => $toSearch,
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

        $eventName = $event->getName();
        $eventCity = $event->getCity()->getName();

        $em = $this->getDoctrine()->getManager();
        $em->remove($event);
        $em->flush();

        $this->addFlash("success", "El evento '$eventName - $eventCity' ha sido eliminado con éxito.");

        return $this->redirectToRoute('events');
    }
}