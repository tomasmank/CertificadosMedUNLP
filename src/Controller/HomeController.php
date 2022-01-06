<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Attendee;
use App\Entity\Event;

/**
 * @Route("/")
 */
class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        return $this->render('app/private/index.html.twig');
    }

    /**
     * @Route("/certificados", name="public")
     */
    public function certificados(Request $request): Response
    {
        
        $dni = null;
        $attendee = null;
        $assistedEvents = null;

        if ($request->query->has('dni')) {
            $dni = $request->query->get('dni');
            $attendee = $this->getDoctrine()
                ->getRepository(Attendee::class)
                ->findOneBy(['dni' => $dni]);
            if ($attendee) {
                $attendances = $attendee->getEventAttendees();
            }
        }

        return $this->render('app/public/index.html.twig', [ 'dni' => $dni , 'attendee' => $attendee, 'attendances' => $attendances ]);
    }
}
