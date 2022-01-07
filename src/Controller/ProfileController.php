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
use Symfony\Component\HttpFoundation\Request;

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
    

    public function create(Request $request)
    {
        $roles = $this->getDoctrine()->getRepository(Role::Class)->findAll();
        $this->render('app/private/profile/new.html.twig',['roles' => $roles ]);

        $profileName = $request->query->get("profileName");  
        $cityID = $request->query->get("cityID");  
          

        if (is_null($profileName){
                /*    PONER MENSAJE DE ERROR
        
                $this->addFlash('notice', 'El evento '.$eventName.' ya existe.');  */
        
            echo('Debe ingresar un nombre');
                        
            return $this->render('app/private/profile/new.html.twig',[]);
        }

        $em = $this->getDoctrine()->getManager();

        $city = $this->getDoctrine()
        ->getRepository(City::class)
        ->find($cityID);

        if ($profileName != null) {
                
            $profile = new Profile();
            $profile->setName($profileName)
                ->setCity($city)
                
            $em->persist($profile);
            $em->flush();

            $profile = $this->getDoctrine()
                ->getRepository(Profile::class)
                ->findAll(); 
                
            return $this->render('app/private/profile/index.html.twig',[
                'perfiles' => $perfiles,
            ]);  

            /*    $this->addFlash('success', 'Evento creado con éxito!');
        
                ACA HABRIA QUE PONER UN POPUP DE CONFIRMACION */
        }
        else {
                /*    MENSAJE DE ERROR
    
                $this->addFlash('notice', 'El evento '.$eventName.' ya existe.');  */
        
            echo('Ya existe un profile con los mismos datos que los ingresados.');
                            
            return $this->render('app/private/profile/new.html.twig',[]);
        }
    }  

    

     /**
     * @Route("/delete", name="deleteProfile")
     */
    public function deleteProfile(Request $request)
    {
        $profileID = $request->query->get("id"); 

        $profile = $this->getDoctrine()
            ->getRepository(Profile::class)
            ->find($profileID);

        $em = $this->getDoctrine()->getManager();
        $em->remove($profile);
        $em->flush();

        $profile = $this->getDoctrine()
            ->getRepository(Profile::class)
            ->findAll();
        
        return $this->render('app/private/profile/index.html.twig',[
            'perfiles' => $profile,
        ]);
    }

    
    
}