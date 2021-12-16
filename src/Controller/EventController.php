<?php

namespace App\Controller;
use App\Repository\EventRepository;
use App\Entity\Event;
use App\Entity\City;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query\AST\Join;

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
     * @Route("/new", name="createEvent")
     */
    public function Create(): Response
    {
        return $this->render('app/private/event/new.html.twig');
    }

    /**
     * @Route("/buscar/{toSearch}", name="buscarEventos")
     */
    public function fullSearch(string $toSearch)
    {
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
}
