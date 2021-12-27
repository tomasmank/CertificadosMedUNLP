<?php

namespace App\Controller;

use App\Repository\TemplateRepository;
use App\Entity\Template;
use App\Form\TemplateType;

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
    public function newTemplate(Request $request): Response
    {
        $template = new Template();
        $form = $this->createForm(TemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $template = $form->getData();

            /** @var UploadedFile $headerFile */
            $headerFile = $form->get('header')->getData();
            if ($headerFile) {
                $originalFilename = pathinfo($headerFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$headerFile->guessExtension();
                
                try {
                    $headerFile->move(
                        $this->getParameter('headers_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // manejar excepcion
                }
                $template->setHeader($newFilename);
            }

            /** @var UploadedFile $signaturesFile */
            $signaturesFile = $form->get('signatures')->getData();
            if ($signaturesFile) {
                $originalFilename = pathinfo($signaturesFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$signaturesFile->guessExtension();
                
                try {
                    $signaturesFile->move(
                        $this->getParameter('signatures_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // manejar excepcion
                }
                $template->setSigns($newFilename);
            }

            /** @var UploadedFile $footerFile */
            $footerFile = $form->get('footer')->getData();
            if ($footerFile) {
                $originalFilename = pathinfo($footerFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$footerFile->guessExtension();
                
                try {
                    $footerFile->move(
                        $this->getParameter('footers_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // manejar excepcion
                }
                $template->setFooter($newFilename);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($template);
            $em->flush();
            $this->addFlash("success", "Template creado con éxito.");
        }


        
        return $this->render('app/private/template/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/detail/{id}", name="detailTemplate")
     */
    public function detailTemplate(Request $request, int $id): Response
    {
        $template = $this->getDoctrine()
            ->getRepository(Template::class)
            ->find($id);

        if ($template) {
            return $this->render('app/private/template/detail.html.twig', [
                'template' => $template,
                'uploads' => $this->getParameter('uploads_directory'),
            ]);
        } else {
            $this->addFlash("error", "No existe template con id: $id.");
            return $this->redirectToRoute('templates');
        }
    }
}
