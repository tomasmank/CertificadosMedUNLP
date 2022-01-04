<?php

namespace App\Form;

use App\Entity\Template;

use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nombre'
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Cuerpo del certificado'
            ])
            ->add('comments', TextType::class, [
                'label' => 'Comentarios'
            ])
            ->add('backgroundColor', ColorType::class, [
                'label' => 'Color de fondo'
            ])
            ->add('header', FileType::class, [
                'label' => 'Encabezamiento',

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using annotations
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                            'application/png',
                            'application/x-png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PNG document',
                    ])
                ],
            ])
            ->add('signatures', FileType::class, [
                'label' => 'Firmas',

                'mapped' => false,

                'required' => false,

                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                            'application/png',
                            'application/x-png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PNG document',
                    ])
                ],
            ])
            ->add('footer', FileType::class, [
                'label' => 'Pie',

                'mapped' => false,

                'required' => false,

                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                            'image/jpeg',
                            'application/png',
                            'application/x-png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PNG document',
                    ])
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Crear template'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Template::class,
        ]);
    }
}
