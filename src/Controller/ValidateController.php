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
    public function validateNames(string $name): Response
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
}