<?php

namespace App\Controller;

use App\Repository\TemplateRepository;
use App\Entity\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/new", name="newTemplate")
     */
    public function newTemplate(): Response
    {
        return $this->render('app/private/template/new.html.twig');
    }

    /**
     * @Route("/create", name="createTemplate")
     */
    public function createTemplate(Request $request): Response
    {
        $name = $request->request->get("name");  
        $comments = $request->request->get("comments"); 
        $body = $request->request->get("body");
        $background_color = $request->request->get("background_color");  
        $header_img = $request->request->get("headerImg");  
        $signatures_img = $request->request->get("signaturesImg");  
        $footer_img = $request->request->get("footerImg");

        $tr = $this->getDoctrine()               // busco otro evento con los datos ingresados
            ->getRepository(Template::class);
        
        if ($tr->alreadyExists($name)){
            $this->addFlash("error", "Ya existe un template con nombre {$name}");
            return $this->render('app/private/template/new.html.twig');
        }

        $template = new Template();
        $template->setName($name)
            ->setComments($comments)
            ->setBody($body)
            ->setHeader($header_img)
            ->setSigns($signatures_img)
            ->setFooter($footer_img);

        $em = $this->getDoctrine()->getManager();
        $em->persist($template);
        $em->flush();

        $this->addFlash("success", "Template '{$name}' creado con éxito.");
        return $this->redirectToRoute('templates');
    }
}
