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
     * @Route("/validateAttendeeData", name="validateAttendeeData")
     */
    public function validateAttendeeData(string $firstName, $lastName, $email, $dni, $cond)
    {
        $errors = [];

        $verifyFN = $this->validateName($firstName)->getContent();

        if ($verifyFN != 0) {
            $errors[] = 'El nombre ingresado ('.$firstName.') contiene caracteres no admitidos.';
        }

        $verifyLN = $this->validateName($lastName)->getContent();

        if ($verifyLN != 0) {
            $errors[] = 'El apellido ingresado ('.$lastName.') contiene caracteres no admitidos.';
        }

        $verifyEmail = $this->validateEmail($email)->getContent();

        if ($verifyEmail != 0) {
            $errors[] = 'El email ingresado ('.$email.') no tiene un formato correcto o contiene caracteres no admitidos.';
        }

        $verifyCond = $this->validateName($cond)->getContent();

        if ($verifyCond != 0) {
            $errors[] = 'La condición ingresada ('.$cond.') contiene caracteres no admitidos.';
        }

        $verifyDNI = $this->validateDni($dni)->getContent();

        if ($verifyDNI != 0) {
            $errors[] = 'El DNI ingresado ('.$dni.') contiene caracteres no admitidos.';
        }

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash("error", $error);
            }
        }

        return new Response(count($errors));
    }
}