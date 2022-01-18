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
use App\Entity\Template;
use App\Controller\ValidateController;
use \DateTime;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Knp\Snappy\Pdf;


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
        $response = $this->forward('App\Controller\ValidateController::validateName', [
            'name'  => $fn,
        ]);
        
        if (($response->getContent()) == 0) {
            
            $response = $this->forward('App\Controller\ValidateController::validateName', [
                'name'  => $ln,
            ]);
            
            if (($response->getContent()) == 0) {

                $em = $this->getDoctrine()->getManager();
                $person = new Person();
                $person->setFirstName($fn);
                $person->setLastName($ln);
                $em->persist($person);
                $em->flush();

                return new Response('Persona registrada con ID: '.$person->getID());
            }
        }
        return new Response('Hubo un error en la definción del nombre '.$fn.' o '.$ln);
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

        return new Response('Se registró el rol (permiso) '.$role->getName().' con ID '.$role->getID());
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
    public function new_City(string $cityName)
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
     * @Route("/new_user/{un}/{pass}/{firstName}/{lastName}/{idProfile}", name="new_user")
     */
    public function newUser(UserPasswordEncoderInterface $passwordEncoder, string $un, $pass, $firstName, $lastName, $idProfile)
    {
        $em = $this->getDoctrine()->getManager();
       
        $profile = $em->find('App\Entity\Profile', $idProfile);

        $user = new User();
        $user
            ->setUsername($un)
            ->setPassword($passwordEncoder->encodePassword($user, $pass))
            ->setFirstName($firstName)
            ->setLastName($lastName)
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

    /**
     * @Route("/find_attendee/{dni}", name="find_attendee")
     */
    public function findAttendee(string $dni): ?Response
    {
        $attendee = $this->getDoctrine()
           ->getRepository(Attendee::class)
            ->findAttendeeByDni($dni);
        if (is_null($attendee)) {
            return new Response('No se encontró ningún asistente con dni '.$dni);    
        }
        return new Response('Asistente con dni '.$dni.': '.$attendee->getFirstName().' '.$attendee->getLastName());
    }

    /**
     * @Route("/fullSearchEvent/{eventSubString}/{citySubString}", name="full_search_event")
     */
    public function searchInEvents(string $eventSubString, string $citySubString)
    {
        $event = $this->getDoctrine()
           ->getRepository(Event::class)
           ->findAll();
    /*        ->fullSearchEvent($eventSubString, $citySubString);
        if (is_null($event)) {
            return new Response('No se encontró ningún evento coincidente con el criterio de búsqueda.');
        }; */
        return $event;
      /* return new Response(count($event)); */
    }

     /**
     * @Route("/searchEvents/{eventSubString}/{citySubString}", name="search_events")
     */
    public function searchEvents(string $eventSubString, string $citySubString)
    {
        $events = $this->searchInEvents($eventSubString, $citySubString);

        if ($events != null) {
            $eventNames = [];
            foreach($events as $e) {
                $eventNames[] = $e->getName(); 
            }
            return $this->render('test/prueba.html.twig', ['nombreColeccion' => 'Eventos encontrados', 'coleccion' => $eventNames]);
        }
    }

    /**
     * @Route("/showPDF", name="show_pdf")
     */
    public function indexAction(\Knp\Snappy\Pdf $snappy)
    {
        #$html = '<h1>Hello</h1>';
        #$html = $this->renderView('hello-world.html.twig');
        $html = $this->renderView('index.html.twig');
        
        $filename = 'myFirstSnappyPDF';

        return new Response(
            $snappy->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'inline; filename="'.$filename.'.pdf"'
            )
        );
    }

    /* 'find($id)' - Método predefinido en cada repositorio de cada clase - Devuelve un objeto o null */

    public function findOneEventById(int $id)
    {
        $event = $this->getDoctrine()
           ->getRepository(Event::class)
           ->find($id);

        return $event;
    }

    /* 'findOneBy(array de criterios)' - Método predefinido en cada repositorio de cada clase - Devuelve un evento o null */

    public function findOneEventBy(string $name, City $city)
    {

        $event = $this->getDoctrine()
            ->getRepository(Event::class)
            ->findOneBy([
                'name' => $name,
                'city' => $city,
            ]);

        return $event;
    }

    /* 'findAll()' - Método predefinido en cada repositorio de cada clase - Devuelve un array */

    public function findAllEvents()
    {
        $events = $this->getDoctrine()
           ->getRepository(Event::class)
           ->findAll();

        return $events;
    }

    /* 'findBy()' - Método predefinido en cada repositorio de cada clase - Devuelve un array     */
    /*  esta función es un ejemplo, donde solo busca por dos criterios (ver la cuestión del NULL) */

    public function findEventsBy(string $name, City $city)
    {
        $events = $this->getDoctrine()
           ->getRepository(Event::class)
           ->findBy([
               'name' => $name,
               'city' => $city,
           ]);

        return $events;
    }

    

    /**
     * @Route("/getCertificate/{dni}/{idEvent}", name="get_certificate")
     */
    public function getCertificate(string $dni, int $idEvent) {
                          
        $events = $this->findAllEvents();

    //    return new Response($event->getName());

    //return $this->render('test/prueba.html.twig', ['nombreColeccion' => 'Eventos encontrados', 'coleccion' => $events]);
    return $this->render('test/prueba2.html.twig', ['nombreColeccion' => 'Eventos encontrados', 'coleccion' => $events]);
 }

    /**
     * @Route("/prueba", name="prueba")
     */
    public function prueba() {
                          
           $events = $this->findAllEvents();

       //    return new Response($event->getName());

       //return $this->render('test/prueba.html.twig', ['nombreColeccion' => 'Eventos encontrados', 'coleccion' => $events]);
       return $this->render('test/prueba2.html.twig', ['nombreColeccion' => 'Eventos encontrados', 'coleccion' => $events]);
    }

    /**
     * @Route("/prueba2/{name}/{id}", name="prueba2")
     */
    public function prueba2(string $name, int $id) {
                          
        $city = $this->getDoctrine()->getRepository(City::class)->find($id);

        $events = $this->findEventsBy($name, $city);

    //    return new Response($event->getName());

    //return $this->render('test/prueba.html.twig', ['nombreColeccion' => 'Eventos encontrados', 'coleccion' => $events]);
    return $this->render('test/prueba2.html.twig', ['nombreColeccion' => 'Eventos encontrados', 'coleccion' => $events]);
    }

    /**
     * @Route("/new_template/{templateName}", name="new_template")
     */
    public function newTemplate(string $templateName)
    {
        $em = $this->getDoctrine()->getManager();
        
        $template = new Template();
        $template->setName($templateName);
                
        $em->persist($template);
        $em->flush();

        return new Response('Se registró el template '.$template->getName().' con ID '.$template->getID());
    }

    /**
     * @Route("/importcsv", name="importcsv")
     */
    public function importCSV2()
    {   
        if (($file = fopen("../Libro1.csv", "r")) !== FALSE) {
            $em = $this->getDoctrine()->getManager();
            $row = 0;
            while (($data = fgetcsv($file, 0, ",")) !== FALSE) {
                $number = count($data);
                $row++;
                echo "<p> Campos leidos en fila $row: $number <br/></p>\n";
                
                echo "<p>$data[1]<br/></p>\n";
                echo "<p>$data[2]<br/></p>\n";
                if ($data[3] == '') {
                    echo "<p>Campo vacío<br/></p>\n";
                }
                else {
                    echo "<p>$data[3]<br/></p>\n";
                }
                echo "<p>$data[4]<br/></p>\n";
                echo "<p>$data[5]<br/></p>\n";        
                
            }
            fclose($file);
        }
        return new Response();
    }

    /**
     * @Route("/strpos", name="strpos")
     */
    public function strpos()
    {   $data = [];
        $data[0] = "apellido";
        if (strpos("Apellidosapellido", $data[0]) !== false) {
            echo ($data[0].' se encuentra en la cadena.');
        }
        else {
            echo ($data[0].' no se encuentra en la cadena.');
        }
        return new Response();
    }

    /**
     * @Route("/strrchr", name="strrchr")
     */
    public function strrchr()
    {   $caracter = '.';
        
        $x = strrchr("Ap.ellid.osape.ido", $caracter);

        if ($x !== false) {
            echo ("Posición del último caracter '$caracter': ".$x);
        }
        else {
            echo ('El caracter elegido no se encuentra en la cadena.');
        }
        return new Response();
    }

}
