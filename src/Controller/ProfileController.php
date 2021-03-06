<?php

namespace App\Controller;
use App\Repository\ProfileRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Entity\Profile;
use App\Entity\Role;
use App\Entity\User;
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
        
        if ($profile != null) {   
            $this->addFlash("error", "El nombre de rol ya existe.");     
            return $this->redirectToRoute('newProfile',['permisos'=>$permisos,'profile'=>$profile]);     
        }

        if ($profileName == '') {
            $this->addFlash("error", "El nombre del rol no puede estar vac??o.");
            return $this->redirectToRoute('newProfile',['permisos'=>$permisos,'profile'=>$profile]);     
        }

        if (empty($permisos)) {
            $this->addFlash("error", "Debe asignarse al menos un permiso.");
            return $this->redirectToRoute('newProfile',['permisos'=>$permisos,'profile'=>$profile]);     
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
        
        $em->flush();
       
        $this->addFlash("success", "El nuevo rol ha sido creado con ??xito.");

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

        return $this->render('app/private/profile/new.html.twig',[
                'permisos' => $permisos,
                'profile' => $profile 
            ]
         );
    
    }

    /**
     
     * @Route(path="/edit", methods={"POST"}, name="saveProfile")
     */
    
    public function saveProfile(Request $request): Response
    {
        $profileID = $request->query->get('id');
        $em = $this->getDoctrine()->getManager();
     
        $profile = $this->getDoctrine()->getRepository(Profile::class)->find($profileID);

        if ($profile->getName() == 'Administrador') {
            $this->addFlash("error","El rol 'Administrador' no puede ser editado");
            return $this->redirectToRoute('profile');
        }

        $newProfileName = $request->request->get('profileName') ?? $profile->getName();
        $newPermisos = $request->request->get('permisos');

        $duplicated = 0;

        $duplicated = $this->getDoctrine()            
                        ->getRepository(Profile::class)
                        ->findDuplicated($profileID, $newProfileName);

        if ($duplicated != 0) {
            $this->addFlash("error","El nombre de rol '$newProfileName' ya existe");
            $roles = $this->getDoctrine()->getRepository(Role::Class)->findAll();
            foreach ($roles as $role) {
                $permiso = array( 'role' => $role, 'checked' => false );
                 foreach ($profile->getRoles() as $profileRole) {
                     if ($profileRole==$role) {
                         $permiso['checked'] =true;
                     }   
                 }
                 $permisos[] = $permiso;
             }
            return $this->render('app/private/profile/new.html.twig',[
                    'permisos' => $permisos,
                    'profile' => $profile 
                ]
             );
        }

        $profile->setName($newProfileName);

        foreach ($profile->getRoles() as $profileRole) {
            $profile->removeRole($profileRole);
        }

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
        
        if ($profile->getname() == 'Administrador') {
            $this->addFlash("error","El perfil 'Administrador' no puede ser eliminado");
            return $this->redirectToRoute('profile');
        }

        $profileOwner = $this->getDoctrine()
            ->getRepository(User::class)
            ->findByProfile($profile);

        if ($profileOwner != null) {
            $ownerName = $profileOwner->getUsername();
            $profileName = $profile->getName();
            $this->addFlash("error","El perfil '$profileName' est?? asignado al usuario '$ownerName'");
            return $this->redirectToRoute('profile');
        }

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
