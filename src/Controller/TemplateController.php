<?php

namespace App\Controller;

use App\Repository\TemplateRepository;
use App\Entity\Template;
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
            return $this->render('app/private/template/detail.html.twig', [
                'template' => $template,
                'uploads' => $this->getParameter('uploads_directory'),
            ]);
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
    public function deleteTemplate(Request $request, int $id): Response
    {
        $template = $this->getDoctrine()
            ->getRepository(Template::class)
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
                $this->addFlash("error", "No se puede eliminar el template porque existen eventos que lo usan. Cambie el template en estos eventos o elimine los eventos y vuelva a intentarlo.");
                return $this->redirectToRoute('detailTemplate', [ 'id' => $id ]);
            }

        } else {
            $this->addFlash("error", "No se puede eliminar el template porque no existe.");
        }

        return $this->redirectToRoute('templates');
    }
}
