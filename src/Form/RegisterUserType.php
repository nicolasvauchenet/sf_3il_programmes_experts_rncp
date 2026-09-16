<?php

namespace App\Form;

use App\Dto\RegisterUserInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RegisterUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'required' => true,
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
                'required' => true,
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
                'required' => true,
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'first_options' => [
                    'label' => 'Mot de passe',
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
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password',
                        'data-password-visibility-target' => 'input',
                    ],
                    'label_attr' => [
                        'class' => 'form-label',
                    ],
                ],
            ])
            ->add('role', ChoiceType::class, [
                'required' => true,
                'label' => 'Rôle',
                'choices' => [
                    'Apprenant' => 'ROLE_STUDENT',
                    'Enseignant' => 'ROLE_TEACHER',
                    'Interne' => 'ROLE_USER',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'label_attr' => [
                    'class' => 'form-label',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterUserInput::class,
        ]);
    }
}
