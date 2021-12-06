<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Person;
use App\Entity\Role;
use App\Entity\Profile;
use App\Entity\City;
use App\Entity\Event;
use App\Entity\Attendee;
use \DateTime;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class TestController extends AbstractController
{
    /**
     * @Route("/test", name="test")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/TestController.php',
        ]);
    }

    /**
     * @Route("/test1", name="test1")
     */
    public function listarItems(): Response
    {
        
        $listado = [];
        $listado[] = 1;
        $listado[] = 5;
        $listado[] = 4;
        $listado[] = 8;
        
        return $this->render('test/prueba.html.twig', ['nombreColeccion' => 'Números', 'coleccion' => $listado]);
    }

    /**
     * @Route("/private/index", name="private_index")
     */
    public function showPrivateIndex(): Response
    {
        return $this->render('private/index.html.twig');
    }

    /**
     * @Route("/private/login", name="private_login")
     */
    public function showPrivateLogin(): Response
    {
        return $this->render('private/login.html.twig');
    }

    /**
     * @Route("/private/event/index", name="private_event_index")
     **/
    public function showPrivateEventIndex(): Response
    {
        return $this->render('private/event/index.html.twig');
    }

    /**
     * @Route("/private/event/new", name="private_event_new")
     */
    public function showPrivateEventNew(): Response
    {
        return $this->render('private/event/new.html.twig');
    }

    /**
     * @Route("/private/user/index", name="private_user_index")
     */
    public function showPrivateUserIndex(): Response
    {
        return $this->render('private/user/index.html.twig');
    }

    /**
     * @Route("/private/user/login", name="private_user_login")
     */
    public function showPrivateUserLogin(): Response
    {
        return $this->render('private/user/login.html.twig');
    }

    /**
     * @Route("/public/index", name="public_index")
     */
    public function showPublicIndex(): Response
    {
        return $this->render('public/index.html.twig');
    }

    /**
     * @Route("/new_person/{fn}/{ln}", name="new_person")
     */
    public function newPerson(string $fn, $ln)
    {
        $em = $this->getDoctrine()->getManager();
        
        $person = new Person();
        $person->setFirstName($fn);
        $person->setLastName($ln);
        
        $em->persist($person);
        $em->flush();

        return new Response('Persona registrada con ID: '.$person->getID());
    }

    /**
     * @Route("/new_role/{roleName}", name="new_role")
     */
    public function newRole(string $roleName)
    {
        $em = $this->getDoctrine()->getManager();
        
        $role = new Role();
        $role->setName($roleName);
                
        $em->persist($role);
        $em->flush();

        return new Response('Se registró el rol (permiso) '.role->getName().' con ID '.$role->getID());
    }

    /**
     * @Route("/new_profile/{profileName}", name="new_profile")
     */
    public function newProfile(string $profileName)
    {
        $em = $this->getDoctrine()->getManager();
        
        $profile = new Profile();
        $profile->setName($profileName);
                
        $em->persist($profile);
        $em->flush();

        return new Response('Se registró el perfil de usuario '.$profile->getName().' con ID '.$profile->getID());
    }

    /**
     * @Route("/new_city/{cityName}", name="new_city")
     */
    public function newCity(string $cityName)
    {
        $em = $this->getDoctrine()->getManager();
        
        $city = new City();
        $city->setName($cityName);
                
        $em->persist($city);
        $em->flush();

        return new Response('Se registró la ciudad '.$city->getName().' con ID '.$city->getID());
    }

    /**
     * @Route("/new_event/{eventName}/{start}/{end}/{idCity}/{status}", name="new_event")
     */
    public function newEvent(string $eventName, string $start, $end, int $idCity, bool $status)
    {
        $startD = DateTime::createFromFormat('Y-m-d', $start);
        $endD = DateTime::createFromFormat('Y-m-d', $end);
               
        $em = $this->getDoctrine()->getManager();
        $city = $em->find('App\Entity\City', $idCity);
                
        $event = new Event();
        $event->setName($eventName);
        $event->setCity($city);
        $event->setStartDate($startD);
        $event->setEndDate($endD);
        $event->setPublished($status);
                
        $em->persist($event);
        $em->flush();

        return new Response('Se registró el evento '.$event->getName().' con ID '.$event->getID());
    }

    /**
     * @Route("/new_attendee/{idEvent}/{fn}/{ln}/{doc}/{mail}/{cond}", name="new_attendee")
     */
    public function newAttendee(int $idEvent, string $fn, $ln, $doc, $mail, $cond)
    {
        $em = $this->getDoctrine()->getManager();
        $event = $em->find('App\Entity\Event', $idEvent);
                
        $attendee = new Attendee();
        $attendee->setFirstName($fn);
        $attendee->setLastName($ln);
        $attendee->setDni($doc);
        $attendee->setEmail($mail);
        $attendee->setCond($cond);
                
        $em->persist($attendee);
        $em->flush();

        return new Response('Se registró el asistente '.$attendee->getFirstName().' '.$attendee->getLastName().' con ID '.$attendee->getID());
    }

    /**
     * @Route("/assign_role/{idProfile}/{idRole}", name="assign_role")
     */
    public function assignRole(int $idProfile, $idRole)
    {
        $em = $this->getDoctrine()->getManager();
       
        $profile = $em->find('App\Entity\Profile', $idProfile);
        $role = $em->find('App\Entity\Role', $idRole);

        $profile->addRole($role);
        
        $em->persist($profile);
        $em->flush();

        return new Response('Se agregó el rol '.$role->getName().' al profile '.$profile->getName());
    }


    /**
     * @Route("/new_user/{un}/{pass}/{idPerson}/{idProfile}", name="new_user")
     */
    public function newUser(UserPasswordEncoderInterface $passwordEncoder, string $un, $pass, int $idPerson, $idProfile)
    {
        $em = $this->getDoctrine()->getManager();
       
        $person = $em->find('App\Entity\Person', $idPerson);
        $profile = $em->find('App\Entity\Profile', $idProfile);

        $user = new User();
        $user
            ->setUsername($un)
            ->setPassword($passwordEncoder->encodePassword($user, $pass))
            ->setPerson($person)
            ->setProfile($profile);
        
        $em->persist($user);
        $em->flush();

        return new Response('Se registró al username '.$user->getUsername().' con ID '.$user->getID());
    }

    /**
     * @Route("/list_roles/{idUser}", name="list_roles")
     */
    public function listRoles(int $idUser): Response
    {
        $em = $this->getDoctrine()->getManager();
       
        $user = $em->find('App\Entity\User', $idUser);

        return $this->render('test/prueba.html.twig', ['nombreColeccion' => 'Roles', 'coleccion' => $user->getRoles()]);
    }
}
