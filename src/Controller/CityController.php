<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\City;

/**
 * @Route("/city")
 */
class CityController extends AbstractController
{
    /**
     * @Route("/", name="city")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CityController.php',
         ]);
    }

    /**
     * @Route("/new", name="newCity")
     */
    public function newCity(): Response
    {
        return $this->render('app/private/city/new.html.twig',[]);
    }

    /**
     * @Route("/create", name="createCity")
     */
    public function createCity(Request $request): Response
    {
        $cityName =  $request->query->get("cityName");  

        if ($cityName =='') {
        
            $this->addFlash("error", "El nombre de la ubicación no puede estar vacío.");
                        
            return $this->render('app/private/city/new.html.twig',[]);
        }

        $response = $this->forward('App\Controller\ValidateController::validateName',[
            'name' => $cityName,
        ]);

        if ($response->getContent() > 0) {
            
            $this->addFlash("error", "En nombre de ubicación ingresado ($cityName) contiene caracteres no admitidos.");
            return $this->redirectToRoute('newCity');
        }
        else {  

            $city = $this->getDoctrine()
            ->getRepository(City::class)
            ->findOneBy([
                'name' => $cityName,
            ]);
            
            if (is_null($city)) {

                $em = $this->getDoctrine()->getManager();
                $city = new City();
                $city->setName($cityName);
                $em->persist($city);
                $em->flush();

                $this->addFlash("success", "Ciudad '$cityName' creada con éxito!");
            }
            else {
                $this->addFlash("error", "La ciudad '$cityName' ya existe.");
            }

            return $this->redirectToRoute('newEvent');
        }
    }
}
