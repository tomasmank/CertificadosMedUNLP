<?php

namespace App\Controller;
use App\Repository\RoleRepository;
use App\Entity\Role;
use App\Repository\ProfileRepository;
use App\Entity\Profile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query\AST\Join;

/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/", name="profile", methods={"GET"})
     */
    public function Index(ProfileRepository $profileRepository): Response
    {
        return $this->render('app/private/profile/index.html.twig',[
            'perfiles' => $profileRepository->findAll(),
        ]);
    }
    /**
     * @Route("/new", name="createProfile")
     */
    public function Create(): Response
    {
            return $this->render('app/private/profile/new.html.twig',[
            'roles' => $roleRepository->findAll(),
        ]);
        
    }

    
    
}