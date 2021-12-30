<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\HttpFoundation\JsonResponse;

class ValidateController extends AbstractController
{
    /**
     * @Route("/validate", name="validate")
     */
    public function index(): Response
    {
        return $this->render('validate/index.html.twig', [
            'controller_name' => 'ValidateController',
        ]);
    }

    /**
     * @Route("/validateName/{name}", name="validate_name")
     */
    public function validateName(string $name): Response
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($name, [
            new Length(['max' => 100]),
            new NotBlank(),
            new Regex('/^[a-zñáéíóúäëïöüàèìòùâêîôû\s]{1,100}$/i'),
        ]);

        return new Response(count($violations));

    /*    if (0 !== count($violations)) {
            
            foreach ($violations as $violation) {
                echo $violation->getMessage().'<br>';
            }
            return new Response('El nombre ingresado ('.$name.') es incorrecto'); 

        }
        else {
            return new Response('El nombre ingresado ('.$name.') es correcto');
        } */
    }

    /**
     * @Route("/validateDateFormat/{dateString}", name="validate_dateFormat")
     */
    public function validateDateAsString(string $dateString)
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($dateString, [
            new Length(['min' => 10,
                        'max' => 10,]),
            new NotBlank(),
            new Regex('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'),
        ]);

        return new Response(count($violations));
    }

    /**
     * @Route("/validateEmail/{email}", name="validate_email")
     */
    public function validateEmail(string $email)
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($email, [
            new Email(['mode' => 'strict'])
        ]);

        return new Response(count($violations));
    }

    /**
     * @Route("/validateDni/{dni}", name="validate_dni")
     */
    public function validateDni(string $dni)
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($dni, [
            new Length(['min' => 8,
                        'max' => 14,]),
            new NotBlank(),
            new Regex('/^[a-z0-9]{8,14}$/i'),
        ]);
        
        return new Response(count($violations));
    }

    /**
     * @Route("/validateAttendeesData", name="validateAttendeesData")
     */
    public function validateAttendeesData(array $data)
    { 
        $errors = [];
        echo('Tipo de dato de $errores1: '.gettype($errors));
        if ($data[1] == '' or $data[2] == '' or $data[3] == '' or $data[4] == '' or $data[5] == '') {
            $errors[] = 'Error - Existen campos vacíos en la planilla importada.';
        }
        echo('Tipo de dato de $errores2: '.gettype($errors));
        if (($this->validateName($data[1])->getContent()) != 0) {
            $errors[] = 'El apellido del asistente con DNI '.$data[4].' es incorrecto.';
        }
        echo('Tipo de dato de $errores3: '.gettype($errors));
        echo('    $data[2]: '.$data[2]);
        if (($this->validateName($data[2])->getContent()) != 0) {
            $errors[] = 'El nombre del asistente con DNI '.$data[4].' es incorrecto.';
            echo('Nombre incorrecto');
        }
        echo('Tipo de dato de $errores4: '.gettype($errors));
        if (($this->validateEmail($data[3])->getContent()) != 0) {
            $errors[] = 'El email del asistente con DNI '.$data[4].' es incorrecto.';
        }
        echo('Tipo de dato de $errores5: '.gettype($errors));
        if (($this->validateDni($data[4])->getContent()) != 0) {
            $errors[] = 'El DNI del asistente '.$data[1].', '.$data[2].' es incorrecto.';
            echo('    DNI incorrecto   ');
        }
        echo('Tipo de dato de $errores6: '.gettype($errors));
        if (($this->validateName($data[5])->getContent()) != 0) {
            $errors[] = 'La condición del asistente con DNI '.$data[4].' es incorrecta.';
        }
        echo('Tipo de dato de $errores7: '.gettype($errors));
echo('Cantidad de errores: '.count($errors));
        return new JsonResponse($errors);
    }

}