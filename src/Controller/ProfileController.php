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
     * @Route("/new", name="newProfile")
     */
    public function newProfile(Request $request): Response
    {
        $profile = new Profile();   
        $roles = $this->getDoctrine()
        ->getRepository(Role::Class)
        ->findAll();
       
        $permisos = array();

        foreach ($roles as $role) {
           $permisos[] = array( 'role' => $role, 'checked' => false );
        }
        //return $this->redirectToRoute('createProfile',['permisos' => $permisos,'profile' => $profile]);
        return  $this->render('app/private/profile/new.html.twig',['permisos' => $permisos,'profile' => $profile ]);
    }
    
    /**
     * @Route("/create", name="createProfile")
     */
    public function create(Request $request, ProfileRepository $profileRepository): Response
    {
        $profileName = $request->request->get("profileName"); 
        $permisos = $request->request->get('permisos');
        
        $roles = $this->getDoctrine()->getRepository(Role::Class)->findAll();
        $profile = $this->getDoctrine()
                ->getRepository(Profile::class)
                ->findOneBy([
                    'name' => $profileName
                ]);
        
        if ($profile!= null) {   
            $this->addFlash("error", "El nombre de rol ya existe.");     
            return $this->redirectToRoute('newProfile',['permisos'=>$permisos,'profile'=>$profile]); 
            //return $this->redirectToRoute('editProfile',['id'=>$profile->getId()]);
        }
        $em = $this->getDoctrine()->getManager();
        $profile = new Profile();
        $profile->setName($profileName);
        foreach ($permisos as $id) {
            $role = $this->getDoctrine()
                ->getRepository(Role::class)
                ->findOneBy([
                    'id' => $id
                ]);
            $profile->addRole($role);
        }
        $em->persist($profile);
        echo
        $em->flush();
       
        $this->addFlash("success", "El nuevo rol ha sido creado con éxito.");

        return $this->redirectToRoute('profile');
    }
    
     /**
     * @Route("/edit", methods={"GET"}, name="editProfile")
     */
    public function editProfile(Request $request): Response
    {
        $profileID = $request->query->get("id");         
        $em = $this->getDoctrine()->getManager();
        $profile = $em->getRepository('App:Profile')->find($profileID);
               
        $roles = $this->getDoctrine()->getRepository(Role::Class)->findAll();

        $permisos = array();

        foreach ($roles as $role) {
           $permiso = array( 'role' => $role, 'checked' => false );
            foreach ($profile->getRoles() as $profileRole) {
                if ($profileRole==$role) {
                    $permiso['checked'] =true;
                }
               
            }
            $permisos[] = $permiso;
        }

        return $this->render(
            'app/private/profile/new.html.twig',
            [
                'permisos' => $permisos,
                'profile' => $profile 
            ]
         );
    
    }

    /**
     * @Route("/new", name="createProfile")
     * @Route(path="/edit", methods={"POST"}, name="saveProfile")
     */
    public function Create(): Response
    public function saveProfile(Request $request): Response
    {
        $profileID = $request->query->get('id');
        $em = $this->getDoctrine()->getManager();
        // phpstorm
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($profileID);
        $newProfileName = $request->request->get('profileName') ?? $profile->getName();
        $newPermisos = $request->request->get('permisos');

        $profile->setName($newProfileName);

        $selectedRoles = $this->getDoctrine()->getRepository(Role::class)->findBy(array(
            'id' => $newPermisos,
        ));

        foreach ($selectedRoles as $selectedRole) {
            $profile->addRole($selectedRole);
        }
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        $roles = $this->getDoctrine()->getRepository(Role::Class)->findAll();

        $permisos = array();

        foreach ($roles as $role) {
            $permiso = array( 'role' => $role, 'checked' => false );
            foreach ($profile->getRoles() as $profileRole) {
                if ($profileRole==$role) {
                    $permiso['checked'] =true;
                }
            }
            $permisos[] = $permiso;
        }

        return $this->redirectToRoute('profile');
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
