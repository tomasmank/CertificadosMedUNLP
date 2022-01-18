<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Profile;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="users", methods={"GET"})
     */
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('app/private/user/index.html.twig',[
            'users' => $userRepository->sortedUsers(),
        ]);
    }

    /**
     * @Route("/new", name="newUser")
     */
    public function newUser(): Response
    {
        $profiles = $this->getDoctrine()
            ->getRepository(Profile::class)
            ->findAll();

        return $this->render('app/private/user/new.html.twig',[
            'profiles' => $profiles,
        ]);
    }

    /**
     * @Route("/create", name="createUser")
     */
    public function createUser(UserPasswordEncoderInterface $passwordEncoder, Request $request): Response
    {
        $firstName = $request->query->get("firstName"); 
        $lastName = $request->query->get("lastName"); 
        $userName = $request->query->get("userName"); 
        $password = $request->query->get("password"); 
        $confirmPassword = $request->query->get("confirmPassword"); 
        $profileID = $request->query->get("profileID"); 

        if ($firstName == '' or $lastName == '' or $password == '' or $confirmPassword == '' or $profileID == '') {
            $this->addFlash("error", "Debe completarse toda la información del nuevo usuario.");
            return $this->redirectToRoute('newUser');  
        } 

        $duplicatedUserName = $this->getDoctrine()
                ->getRepository(User::class)
                ->findBy([
                    'username' => $userName,
                ]);

        if ($duplicatedUserName != null) {
            $this->addFlash("error", "El nombre de usuario '$userName' ya está definidio en el sistema.");
            return $this->redirectToRoute('newUser');  
        }

        $response = $this->forward('App\Controller\ValidateController::validateUserData',[
            'firstName' => $firstName,
            'lastName' => $lastName,
            'userName' => $userName,
            'password' => $password,
            'confirmPassword' => $confirmPassword,
            'profileID' => $profileID,
        ]);

        if ($response->getContent() > 0) {
            
            return $this->redirectToRoute('newUser');
        }
        else {

            $profile = $this->getDoctrine()
                ->getRepository(Profile::class)
                ->find($profileID);

            $duplicated = $this->getDoctrine()
                ->getRepository(User::class)
                ->findOneBy([
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'username' => $userName,
                ]);
            
            $em = $this->getDoctrine()->getManager();
            
            if ($duplicated != null) {
                $this->addFlash("error", "Ya existe un usuario con nombre $firstName, apellido $lastName y nombre de usuario '$userName'.");
                return $this->redirectToRoute('users');
            }
            else {
                
                $profile = $this->getDoctrine()
                    ->getRepository(Profile::class)
                    ->find($profileID);

                $user = new User();
                $user->setFirstName($firstName)
                    ->setLastName($lastName)
                    ->setUserName($userName)
                    ->setPassword($passwordEncoder->encodePassword($user, $password))
                    ->setProfile($profile);
                $em->persist($user);
                $em->flush();
            }
        
            $this->addFlash("success", "El usuario '$userName' ha sido creado con éxito.");

            return $this->redirectToRoute('users');
        }
    }

    /**
     * @Route("/perfil", name="detailCurrentUser")
     */
    public function detailCurrent(): Response
    {
        $userInSession = $this->getUser();

        $profiles = $this->getDoctrine()
            ->getRepository(Profile::class)
            ->findAll();

        return $this->render('app/private/user/detail-current.html.twig', [
            'user' => $userInSession,
            'inSession' => $userInSession,
            'profiles' => $profiles,
        ]);
    }

    /**
     * @Route("/fullSearchUsers", name="fullSearchUsers")
     */
    public function fullSearchUsers(Request $request)
    {
        $toSearch = $request->query->get("toSearch"); 

        $em = $this->getDoctrine()->getManager();

        $qb = $em->createQueryBuilder('u');
        
        $usersMatching = $qb->select('u')
            ->from('App\Entity\User', 'u')
            ->where('u.username LIKE ?1')
            ->orWhere('u.firstName LIKE ?1')
            ->orWhere('u.lastName LIKE ?1')
            ->orWhere('p.name LIKE ?1')
            ->innerJoin(
                Profile::class, 'p', 'WITH', 'u.profile = p.id')
            ->setParameter(1, '%'.$toSearch.'%')
            ->orderBy('u.username', 'ASC')
            ->getQuery()
            ->execute();

        return $this->render('app/private/user/index.html.twig',[
            'users' => $usersMatching,
        ]);
    }

    /**
     * @Route("/view", name="viewUser");
     */
    public function viewUser(Request $request): Response
    {
        $userID = $request->query->get("userID"); 
        
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($userID);
        
        $userInSession = $this->getUser();

        $profiles = $this->getDoctrine()
            ->getRepository(Profile::class)
            ->findAll();
        
        return $this->render('app/private/user/detail-current.html.twig',[
            'user' => $user,
            'inSession' => $userInSession,
            'profiles' => $profiles,
        ]);
    }

    /**
     * @Route("/delete", name="deleteUser")
     */
    public function deleteUser(Request $request)
    {
        $userID = $request->query->get("userID"); 

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($userID);

        $userName = $user->getUserName();
        
        $remainingAdmin = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAdmins($userID);

        if (count($remainingAdmin) > 0) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
            $this->addFlash("success", "El usuario '$userName' ha sido eliminado con éxito.");
        }
        else {
            $this->addFlash("error", "El usuario que se intenta eliminar es el último con perfil de Administrador.");
            return $this->redirectToRoute('users');
        }

        return $this->redirectToRoute('users');
    }

    /**
     * @Route("/modify", name="modifyUser")
     */
    public function modifyUser(UserPasswordEncoderInterface $passwordEncoder, Request $request)
    {
        $userID = $request->query->get("userID"); 

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($userID);

        $firstName = $request->query->get("firstName"); 
        $lastName = $request->query->get("lastName"); 
        $userName = $request->query->get("userName"); 
        $password = $request->query->get("password"); 
        $confirmPassword = $request->query->get("confirmPassword"); 
        $profileID = $request->query->get("profileID"); 

        if ($firstName == '' or $lastName == '' or $profileID == '') {
            $this->addFlash("error", "Debe completarse toda la información del nuevo usuario.");
            return $this->redirectToRoute('viewUser',[
                'userID' => $userID,
            ]);  
        } 

        $response = $this->forward('App\Controller\ValidateController::validateUserData',[
            'firstName' => $firstName,
            'lastName' => $lastName,
            'userName' => $userName,
            'password' => $password,
            'confirmPassword' => $confirmPassword,
            'profileID' => $profileID,
        ]);

        if ($response->getContent() > 0) {
            
            return $this->redirectToRoute('viewUser', [
                'userID' => $userID,
            ]);
        }
        else {

            $remainingAdmin = $this->getDoctrine()
            ->getRepository(User::class)
            ->findAdmins($userID);

            if (count($remainingAdmin) > 0) {
        
                $profile = $this->getDoctrine()
                    ->getRepository(Profile::class)
                    ->find($profileID);

                $em = $this->getDoctrine()->getManager();
                
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                if (strlen($password) >= 3) {
                    $user->setPassword($passwordEncoder->encodePassword($user, $password));
                }
                $user->setProfile($profile);
                $em->flush();
            
                $this->addFlash("success", "El usuario '$userName' ha sido modificado con éxito.");
                return $this->redirectToRoute('users');
            }
            else {
                $this->addFlash("error", "El usuario que se intenta modificar es el último con perfil de Administrador.");
                return $this->redirectToRoute('users');
            }
        
        }
    }
}