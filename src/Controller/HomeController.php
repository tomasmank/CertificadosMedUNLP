<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    public function certificados(): Response
    {
        return $this->render('app/public/index.html.twig');
    }

  #  /**
  #   * @Route("/login", name="login")
  #   */
  #  public function login(): Response
  #  {
  #      return $this->render('app/private/user/login.html.twig');
  #  }
}
