<?php

namespace App\Controller;

use App\Repository\TemplateRepository;
use App\Entity\Template;
use App\Entity\Event;
use App\Form\TemplateType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

use Knp\Snappy\Pdf;
use Knp\Bundle\SnappyBundle\Snappy\Response\PdfResponse;

/**
 * @Route("/template")
 */
class TemplateController extends AbstractController
{
    /**
     * @Route("/index/{currentPage}", name="templates", methods={"GET"})
     */
    public function Index(TemplateRepository $templateRepository, Request $request, $currentPage = 1): Response
    {
        $perPage = 20;

        $toSearch = $request->query->get("toSearch");

        $templates = $templateRepository->getAll($currentPage, $perPage, $toSearch);
        $templatesResult = $templates['paginator'];
        $templatesFullQuery = $templates['query'];

        $maxPages = ceil($templates['paginator']->count() / $perPage);

        return $this->render('app/private/template/index.html.twig',[
            'templates' => $templatesResult,
            'maxPages'=> $maxPages,
            'thisPage' => $currentPage,
            'all_items' => $templatesFullQuery,
            'searchParameter' => $toSearch
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

            $template_name = $template->getName();

            $em = $this->getDoctrine()->getManager();
            $em->persist($template);
            $em->flush();
            $this->addFlash("success", "Template '$template_name' creado con éxito.");
            return $this->redirectToRoute('templates');
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
            $eventsWithThisTemplate = $this->getDoctrine()
                ->getRepository(Event::class)
                ->findBy(array('template' => $template));

            return $this->render('app/private/template/detail.html.twig', [
                'template' => $template,
                'uploads' => $this->getParameter('uploads_directory'),
                'eventsWithThisTemplate' => $eventsWithThisTemplate
            ]);
        } else {
            $this->addFlash("error", "No existe template con id: $id.");
            return $this->redirectToRoute('templates');
        }
    }

    /**
     * @Route("/update/{id}", name="updateTemplate")
     */
    public function updateTemplate(Request $request, int $id): Response
    {
        $template = $this->getDoctrine()
            ->getRepository(Template::class)
            ->find($id);
        
        $form = $this->createForm(TemplateType::class, $template);
        $form->handleRequest($request);

        if ($template) {

            $eventsWithThisTemplate = $this->getDoctrine()
                    ->getRepository(Event::class)
                    ->findBy(array('template' => $template));

            if ($form->isSubmitted() && $form->isValid()) {
                
                $template->setName($form->get('name')->getData());
                $template->setBody($form->get('body')->getData());
                $template->setComments($form->get('comments')->getData());
                $template->setBackgroundColor($form->get('backgroundColor')->getData());

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
                
                $this->addFlash("success", "Se actualizó el template: " . $template->getName());
                return $this->redirectToRoute('detailTemplate', ['id' => $template->getId()] );
            } else {
                return $this->render('app/private/template/update.html.twig', [
                    'form' => $form->createView(),
                    'template' => $template,
                    'uploads' => $this->getParameter('uploads_directory'),
                    'eventsWithThisTemplate' => $eventsWithThisTemplate
                ]);
            }
        } else {
            $this->addFlash("error", "No existe template con id: $id.");
            return $this->redirectToRoute('templates');
        }
    }

    /**
     * @Route("/example/{id}", name="exampleTemplate")
     */
    public function exampleTemplate(Request $request, int $id)
    {
        $template = $this->getDoctrine()
            ->getRepository(Template::class)
            ->find($id);

        if ($template) {
            $knpSnappyPdf = new Pdf('/usr/bin/wkhtmltopdf');
            $knpSnappyPdf->setOption('lowquality', false);
            $knpSnappyPdf->setOption('disable-javascript', true);
            $knpSnappyPdf->setOption('page-size', 'A4');
            $knpSnappyPdf->setOption('orientation', 'Landscape');
            $knpSnappyPdf->setOption('enable-local-file-access', true);
            $knpSnappyPdf->setOption('images', true);
            $knpSnappyPdf->setOption('margin-bottom', 0);
            $knpSnappyPdf->setOption('margin-left', 0);
            $knpSnappyPdf->setOption('margin-right', 0);
            $knpSnappyPdf->setOption('margin-top', 0);

            $html = $this->renderView('app/private/certificate/example.html.twig', [
                'template'  => $template,
            ]);
        
            return new PdfResponse(
                $knpSnappyPdf->getOutputFromHtml($html),
                'example.pdf',
                array(
                    'images' =>true,            
                )
            );
        } else {
            $this->addFlash("error", "No existe template con id: $id.");
            return $this->redirectToRoute('templates');
        }
    }

     /**
     * @Route("/delete/{id}", name="deleteTemplate")
     */
    public function deleteTemplate(TemplateRepository $templateRepository, Request $request, int $id): Response
    {

        $template = $templateRepository
            ->find($id);

        if($template){
            try {
                $filesystem = new Filesystem();

                if($template->getHeader() !== NULL){
                    $filesystem->remove($this->getParameter('headers_directory') . DIRECTORY_SEPARATOR . $template->getHeader());
                }
                if($template->getSigns() !== NULL){
                    $filesystem->remove($this->getParameter('signatures_directory') . DIRECTORY_SEPARATOR . $template->getSigns());
                }
                if($template->getFooter() !== NULL){
                    $filesystem->remove($this->getParameter('footers_directory') . DIRECTORY_SEPARATOR . $template->getFooter());
                }

                $template_name = $template->getName();
                $em = $this->getDoctrine()->getManager();
                $em->remove($template);
                $em->flush();
                $this->addFlash("success", "Se elimino correctamente el template: $template_name.");

            } catch (IOExceptionInterface $exception) {
                echo "Ocurrió un error intentando eliminar el archivo ".$exception->getPath();
            } catch (ForeignKeyConstraintViolationException $exception) {

                $events = $this->getDoctrine()
                    ->getManager()
                    ->getRepository(Event::class)
                    ->findBy(array('template' => $template));
                $eventNames = array();
                foreach ($events as $event) {
                    array_push($eventNames, $event->getName());
                }

                $this->addFlash("error", "No se puede eliminar el template porque existen eventos que lo usan. Elimine estos eventos o modifique su template: " . join(", ",$eventNames) . ".");
            
                return $this->redirectToRoute('detailTemplate', [ 'id' => $id ]);
            }

        } else {
            $this->addFlash("error", "No se puede eliminar el template porque no existe.");
        }

        return $this->redirectToRoute('templates');
    }

    /**
     * @Route("/deleteImage/{id}/{type}", name="deleteImage")
     */
    public function deleteImage(TemplateRepository $templateRepository, Request $request, int $id, String $type): Response
    {
        $template = $templateRepository
            ->find($id);

        if($template){
            try {
                $filesystem = new Filesystem();

                if ($type == 'header'){
                    if($template->getHeader() !== NULL){
                        $filesystem->remove($this->getParameter('headers_directory') . DIRECTORY_SEPARATOR . $template->getHeader());
                        $template->setHeader(NULL);
                    }
                } elseif ($type == 'signatures') {
                    if($template->getSigns() !== NULL){
                        $filesystem->remove($this->getParameter('signatures_directory') . DIRECTORY_SEPARATOR . $template->getSigns());
                        $template->setSigns(NULL);
                    }
                } elseif ($type == 'footer') {
                    if($template->getFooter() !== NULL){
                        $filesystem->remove($this->getParameter('footers_directory') . DIRECTORY_SEPARATOR . $template->getFooter());
                        $template->setFooter(NULL);
                    }
                }

                $template_name = $template->getName();
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                $this->addFlash("success", "Se elimino correctamente la imagen seleccionada.");

            } catch (IOExceptionInterface $exception) {
                echo "Ocurrió un error intentando eliminar el archivo ".$exception->getPath();
            } 

        } else {
            $this->addFlash("error", "No se puede eliminar el template porque no existe.");
        }

        return $this->redirectToRoute('updateTemplate', [ 'id' => $id ]);
    }
}
