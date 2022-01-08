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
    public function newEvent()
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
        
        if ($eventName == '') {
        
            $this->addFlash("error", "El nombre del evento no puede quedar en blanco.");
                        
            return $this->render('app/private/event/new.html.twig',[]);
        }

        if ($cityID == '') {
        
            $this->addFlash("error", "La ubicación no puede quedar en blanco.");
                        
            return $this->render('app/private/event/new.html.twig',[]);
        }

        $response = $this->forward('App\Controller\ValidateController::validateEventData',[
            'cityID' => $cityID,
            'startDate' => $startDateString,
            'endDate' => $endDateString,
            'templateID' => $templateID,
            'fileName' => $CSVFile,
        ]);

        if ($response->getContent() > 0) {
            
            return $this->redirectToRoute('newEvent');
        }
        else {
            
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
                $this->addFlash("success", "El evento '$eventName - $cityName' ha sido creado con éxito.");
                
                return $this->render('app/private/event/index.html.twig',[
                    'events' => $events,
                ]);  
            }
            else {
                $cityName = $city->getName();
                $this->addFlash("error", "Ya existe otro evento con nombre'$eventName' realizado en $cityName desde el $startDateString hasta el $endDateString.");
            
                return $this->redirectToRoute('newEvent');
            }
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
        $x=gettype($CSVFile);
        $this->addFlash("error","Tipo de dato CSVFile: $x");
        if ($eventID != '') {
            $event = $this->getDoctrine()
                ->getRepository(Event::class)
                ->find($eventID);

            if ($event == null) {
                $this->addFlash("error", "El ID del evento no puede ser modificado.");
                return $this->redirectToRoute('events');
            }
        }
        else {
            $this->addFlash("error", "El ID del evento no puede ser modificado.");
            return $this->redirectToRoute('events');
        }

        if ($published) {
            $published = 1;
        }
        else {
            $published = 0;
        }

        if ($eventName == '') {
        
            $this->addFlash("error", "El nombre del evento no puede quedar en blanco.");
                        
            return $this->redirectToRoute('events');
        }

        if ($cityID == '') {
        
            $this->addFlash("error", "La ubicación no puede quedar en blanco.");
                        
            return $this->redirectToRoute('events');
        }
        
        $response = $this->forward('App\Controller\ValidateController::validateEventData',[
            'cityID' => $cityID,
            'startDate' => $startDateString,
            'endDate' => $endDateString,
            'templateID' => $templateID,
            'fileName' => $CSVFile,
        ]);

        if ($response->getContent() > 0) {
            
            return $this->redirectToRoute('newEvent');
        }
        else {

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

/*


                    if ($CSVFile) {
                        $originalFilename = pathinfo($CSVFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                        $newFilename = $safeFilename.'-'.uniqid().'.'.$CSVFile->guessExtension();
                        
                        try {
                            $CSVFile->move(
                                $this->getParameter('headers_directory'),
                                $newFilename
                            );
                        } catch (FileException $e) {
                            // manejar excepcion
                        }
                    }*/
            




                    $response = $this->updateAttendees($event, $CSVFile);
                    $result = $response->getContent();
                }
                
                if ($result == 0) {
                    $this->addFlash("success", "El evento '$eventName' ha sido modificado con éxito.");
                    return $this->redirectToRoute('events');
                }
                else {
                    $this->addFlash("error", 'El evento no se modificó correctamente.');
                }
            }
            else {
                $cityName = $city->getName();
                $this->addFlash("error", "Ya existe otro evento con nombre '$eventName' realizado en $cityName desde el $startDateString hasta el $endDateString.");
            }

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
                $this->addFlash("error", "Error al abrir el archivo $CSVFile. Verifique que sea de tipo CSV y que contenga los campos requeridos.");
                    return new Response(1);
            }
            
            $em = $this->getDoctrine()->getManager();

            while (($data = fgetcsv($file, 0, ",")) !== FALSE) {

                if ($data[$ln] == '' or $data[$fn] == '' or $data[$email] == '' or $data[$dni] == '' or $data[$cond] == '') {
                    $this->addFlash("error", "Existen campos vacíos en la planilla importada.");
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
                    $this->addFlash("error", "El archivo de asistentes no pudo procesarse. Corrija los errores y vuelva a procesarlo.");
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
            }
            fclose($file);
            $this->addFlash("success", "El archivo de asistentes ha sido procesado correctamente.");
            return new Response (0);
        }   
        else {
            $this->addFlash("error", "Error al abrir el archivo $CSVFile. Verifique que sea de tipo CSV y que contenga los campos requeridos.");
            return new Response(1);
        } 
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