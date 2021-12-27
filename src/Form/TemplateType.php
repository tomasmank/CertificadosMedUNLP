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
            ->add('name', TextType::class)
            ->add('header', FileType::class, [
                'label' => 'Header',

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
                            'application/png',
                            'application/x-png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PNG document',
                    ])
                ],
            ])
            ->add('body', TextareaType::class)
            ->add('signatures', FileType::class, [
                'label' => 'Signatures',

                'mapped' => false,

                'required' => false,

                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                            'application/png',
                            'application/x-png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PNG document',
                    ])
                ],
            ])
            ->add('footer', FileType::class, [
                'label' => 'Footer',

                'mapped' => false,

                'required' => false,

                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/png',
                            'application/png',
                            'application/x-png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PNG document',
                    ])
                ],
            ])
            ->add('comments', TextType::class)
            ->add('backgroundColor', ColorType::class)
            ->add('Crear', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Template::class,
        ]);
    }
}
