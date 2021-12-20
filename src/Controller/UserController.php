<?php

namespace App\Controller;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="users", methods={"GET"})
     */
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('app/private/user/index.html.twig',[
            'users' => $userRepository->findAll(),
        ]);
    }
    /**
     * @Route("/new", name="createUser")
     */
    public function Create(): Response
    {
        return $this->render('app/private/user/new.html.twig');
    }
}
