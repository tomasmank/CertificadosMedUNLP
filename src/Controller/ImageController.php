<?php

namespace App\Controller;

use App\Repository\ImageRepository;
use App\Entity\Image;
use App\Form\ImageType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



class ImageController extends AbstractController
{
    /**
     * @Route("/images", name="images")
     */
    public function index(ImageRepository $imageRepository, Request $request): Response
    {
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $imageName = $form->get('name')->getData();

            if ($imageRepository->findOneBy(array('name' => $imageName)) == NULL){
                /** @var UploadedFile $imageFile */
                $imageFile = $form->get('filename')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageName, PATHINFO_FILENAME);
                    $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                    try {
                        $imageFile->move(
                            $this->getParameter('images_directory'),
                            $newFilename
                        );

                        $image->setName($imageName)
                            ->setFilename($newFilename);

                        $em = $this->getDoctrine()->getManager();
                        $em->persist($image);
                        $em->flush();
                        $this->addFlash("success", "Imagen '$imageName' cargada con éxito.");
                    } catch (FileException $e) {
                        // manejar excepcion
                    }
                } else {
                    $this->addFlash("error", "Por favor, cargue una imagen.");
                }
            } else {
                $this->addFlash("error", "Ya existe una imagen con nombre '$imageName'. Intente con otro nombre.");
            }
            return $this->redirectToRoute('images');
        }

        return $this->render('app/private/image/index.html.twig', [
            'images' => $imageRepository->findAll(),
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/images/load", name="loadImage")
     */
    public function loadImage(): Response
    {
        return $this->render('app/private/image/load.html.twig');
    }
}
