<?php

namespace App\Controller;

use App\Repository\ImageRepository;
use App\Entity\Image;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



class ImageController extends AbstractController
{
    /**
     * @Route("/images", name="images")
     */
    public function index(ImageRepository $imageRepository): Response
    {
        return $this->render('image/index.html.twig', [
            'images' => $imageRepository->getAll(),
        ]);
    }

    /**
     * @Route("/images/load", name="loadImage")
     */
    public function loadImage(): Response
    {
        return $this->render('image/load.html.twig');
    }
}
