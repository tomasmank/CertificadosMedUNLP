<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TemplateController extends AbstractController
{
    /**
     * @Route("/template", name="template")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/TemplateController.php',
        ]);
    }

    /**
     * @Route("/new/{templateName}", name="new_template")
     */
    public function newRole(string $roleName)
    {
        $em = $this->getDoctrine()->getManager();
        
        $role = new Role();
        $role->setName($roleName);
                
        $em->persist($role);
        $em->flush();

        return new Response('Se registró el rol (permiso) '.$role->getName().' con ID '.$role->getID());
    }
}
