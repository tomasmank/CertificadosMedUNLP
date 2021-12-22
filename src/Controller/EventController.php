<?php

namespace App\Controller;
use App\Repository\EventRepository;
use App\Entity\Template;
use App\Entity\City;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Event;

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
        $startDate = $request->query->get("startDate");  
        $endDate = $request->query->get("endDate");  
        $templateID = $request->query->get("templateID");  

        if (is_null($eventName) or is_null($cityID)) {
                /*    PONER MENSAJE DE ERROR
        
                $this->addFlash('notice', 'El evento '.$eventName.' ya existe.');  */
        
            echo('El nombre del evento y el nombre de la ubicación no pueden estar vacíos.');
                        
            return $this->render('app/private/event/new.html.twig',[]);
        }

        $em = $this->getDoctrine()->getManager();

        $city = $this->getDoctrine()
        ->getRepository(City::class)
        ->find($cityID);

        if ($templateID != null) {
                    
            $template = $this->getDoctrine()
                ->getRepository(Template::class)
                ->find($templateID);
        }
        else {
            $template = null;
        }

        if ($startDate != '') {
            $startDate = \DateTime::createFromFormat('Y-m-d', $startDate);
        }
        else {
            $startDate = null;
        }
        if ($endDate != '') {            
            $endDate = \DateTime::createFromFormat('Y-m-d', $endDate);
        }
        else {
            $endDate = null;
        }

        $duplicated = 0;

        if (($startDate != null) and ($endDate != null)) {  // si tengo nombre, ciudad y las dos fechas busco duplicados

            if ($endDate < $startDate) {
                 /*    Y ACA UNO DE ERROR
        
                $this->addFlash('notice', 'El evento '.$eventName.' ya existe.');  */
                        
                return $this->render('app/private/event/new.html.twig',[]);
            }
            
            $duplicated = $this->getDoctrine()               // busco otro evento con los datos ingresados
                ->getRepository(Event::class)
                ->findDuplicated(0, $eventName, $city, $startDate, $endDate);
        }
        if ($duplicated ==0) {
                
            $event = new Event();
            $event->setName($eventName)
                ->setCity($city)
                ->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setTemplate($template)
                ->setPublished(0);
            $em->persist($event);
            $em->flush();

            $events = $this->getDoctrine()
                ->getRepository(Event::class)
                ->findAll(); 
                
            return $this->render('app/private/event/index.html.twig',[
                'events' => $events,
            ]);  

            /*    $this->addFlash('success', 'Evento creado con éxito!');
        
                ACA HABRIA QUE PONER UN POPUP DE CONFIRMACION */
        }
        else {
                /*    MENSAJE DE ERROR
    
                $this->addFlash('notice', 'El evento '.$eventName.' ya existe.');  */
        
            echo('Ya existe un evento con los mismos datos que los ingresados.');
                            
            return $this->render('app/private/event/new.html.twig',[]);
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
        $startDate =  $request->query->get("startDate");  
        $endDate =  $request->query->get("endDate");  
        $templateID =  $request->query->get("templateID");  

        $event = $this->getDoctrine()
        ->getRepository(Event::class)
        ->find($eventID);

        if (is_null($eventName) or is_null($cityID)) {
                /*    PONER MENSAJE DE ERROR
        
                $this->addFlash('notice', 'El evento '.$eventName.' ya existe.');  */
        
            echo('El nombre del evento y la ubicación no pueden estar vacíos.');
                        
            return $this->render('app/private/event/modify.html.twig',['event' => $event]);
        }

        $em = $this->getDoctrine()->getManager();

        $city = $this->getDoctrine()
        ->getRepository(City::class)
        ->find($cityID);

        if ($templateID != null) {
                    
            $template = $this->getDoctrine()
                ->getRepository(Template::class)
                ->find($templateID);
        }

        if ($startDate != '') {
            $startDate = \DateTime::createFromFormat('Y-m-d', $startDate);
        }
        else {
            $startDate = null;
        }
        if ($endDate != '') {            
            $endDate = \DateTime::createFromFormat('Y-m-d', $endDate);
        }
        else {
            $endDate = null;
        }

        $duplicated = 0;

        if (($startDate != null) and ($endDate != null)) {  // si tengo nombre, ciudad y las dos fechas busco duplicados

            if ($endDate < $startDate) {
                 /*    Y ACA UNO DE ERROR
        
                $this->addFlash('notice', 'El evento '.$eventName.' ya existe.');  */
        
                echo('La fecha de finalización es anterior a la decha de inicio.');
                        
                return $this->render('app/private/event/modify.html.twig',['event' => $event]);
            }
echo ('$eventName: '.$eventName.' - ');
echo ('$cityName: '.$city->getName().' - ');
echo ('$startDate: '.$startDate->format('d-m-Y').' - ');
echo ('$endDate: '.$endDate->format('d-m-Y').' - ');
echo ('$eventID: '.$eventID.' - ');

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
                ->setPublished(0);
            $em->persist($event);
            $em->flush();

            $events = $this->getDoctrine()
            ->getRepository(Event::class)
            ->findAll(); 
            
            return $this->render('app/private/event/index.html.twig',[
                'events' => $events,
        ]);  

               /*    $this->addFlash('success', 'Evento creado con éxito!');
        
                ACA HABRIA QUE PONER UN POPUP DE CONFIRMACION */
         
        }
        else {
            /*    MENSAJE DE ERROR

            $this->addFlash('notice', 'El evento '.$eventName.' ya existe.');  */
        
            echo('Ya existe un evento con los mismos datos que los ingresados.');
                            
            return $this->render('app/private/event/modify.html.twig',['event' => $event]);
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

        return $this->render('app/private/event/modify.html.twig',[
            'event' => $event,
            'cities' => $cities,
            'templates' => $templates,
        ]);
    }
}
