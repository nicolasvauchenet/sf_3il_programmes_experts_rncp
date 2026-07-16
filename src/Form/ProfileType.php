<?php

namespace App\Form;

use App\Dto\ProfileInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nom complet',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'name',
                ],
                'label_attr' => [
                    'class' => 'form-label',
                ],
            ])
            ->add('emailPrefix', TextType::class, [
                'label' => 'Adresse e-mail',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'username',
                ],
                'label_attr' => [
                    'class' => 'form-label',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'required' => false,
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'required' => false,
                    'empty_data' => null,
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password',
                        'data-password-visibility-target' => 'input',
                    ],
                    'label_attr' => [
                        'class' => 'form-label',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmation',
                    'required' => false,
                    'empty_data' => null,
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password',
                        'data-password-visibility-target' => 'input',
                    ],
                    'label_attr' => [
                        'class' => 'form-label',
                    ],
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProfileInput::class,
        ]);
    }
}
