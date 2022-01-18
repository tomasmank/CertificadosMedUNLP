<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\City;
use App\Entity\Template;
use App\Repository\CityRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre',
                'invalid_message' => 'El nombre del evento no puede estar vacío.',
            ])

            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => function(City $city) {
                    return $city->getName();
                },
                'choice_value' => function (?City $entity) {
                    return $entity ? $entity->getId() : '';
                },
                'label' => 'Ubicación',
                'placeholder' => 'Elija una ubicación',
                'choices' => $this->em->getRepository(City::class)->findAll(),
            ])

            ->add('startDate', DateType::class, [
                'label' => 'Fecha inicio',
                'required' => false,
                'format' => 'dd-MM-yyyy',
                'years' => range(2021,date('Y') + 5),
                
                'widget' => 'single_text',

                // prevents rendering it as type="date", to avoid HTML5 date pickers
                'html5' => false,

                // adds a class that can be selected in JavaScript
                'attr' => ['class' => 'js-datepicker form-control'],
            ])

            ->add('endDate', DateType::class, [
                'label' => 'Fecha fin',
                'required' => false,
                'format' => 'dd-MM-yyyy',
                'years' => range(2021,date('Y') + 5),

                'widget' => 'single_text',

                // prevents rendering it as type="date", to avoid HTML5 date pickers
                'html5' => false,

                // adds a class that can be selected in JavaScript
                'attr' => ['class' => 'js-datepicker form-control'],
            ])

            ->add('attendeeFile', FileType::class, [
                'label' => 'Archivo de asistentes',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'maxSizeMessage' => 'El tamaño del archivo ({{size}}) excede el máximo permitido ({{limit}})',
                        'mimeTypes' => [
                            'application/csv',
                            'text/csv',
                            'text/plain',
                        ],  
                        'mimeTypesMessage' => 'El archivo seleccionado no es de tipo CSV.',  
                    ])
                ],  
            ])

            ->add('template', EntityType::class, [
                'class' => Template::class,
                'choice_label' => function(Template $template) {
                    return $template->getName();
                },
                'choice_value' => function (?Template $templ) {
                    return $templ ? $templ->getId() : '';
                },
                'label' => 'Template',
                'placeholder' => 'Elija un template',
                'required' => false,
                'choices' => $this->em->getRepository(Template::class)->findAll(), 
            ])

            ->add('published', ChoiceType::class, [
                'label' => 'Publicado',
                'choices'  => [
                    'No' => false,
                    'Si' => true,
                ],                
            ])

            ->add('createEvent', SubmitType::class, [
                'label' => 'Aceptar',
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
