<?php

namespace App\Controller;

use App\Repository\TemplateRepository;
use App\Entity\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/template")
 */
class TemplateController extends AbstractController
{
    /**
     * @Route("/", name="templates", methods={"GET"})
     */
    public function Index(TemplateRepository $templateRepository): Response
    {
        return $this->render('app/private/template/index.html.twig',[
            'templates' => $templateRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="createTemplate")
     */
    public function Create(): Response
    {
        return $this->render('app/private/template/new.html.twig');
    }
}
