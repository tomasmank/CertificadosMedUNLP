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
        $events = null;

        if ($request->request->has('_dni')) {
            $dni = $request->request->get('_dni');
            $attendee = $this->getDoctrine()
                ->getRepository(Attendee::class)
                ->findAttendeeByDni($dni);
            if ($attendee) {
                $events = $attendee->getEvents();
            }
        }

        return $this->render('app/public/index.html.twig', [ 'dni' => $dni , 'attendee' => $attendee , 'events' => $events ]);
    }

  #  /**
  #   * @Route("/login", name="login")
  #   */
  #  public function login(): Response
  #  {
  #      return $this->render('app/private/user/login.html.twig');
  #  }
}
