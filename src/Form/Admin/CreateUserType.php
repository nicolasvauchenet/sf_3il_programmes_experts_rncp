<?php

namespace App\Form\Admin;

use App\Dto\Admin\CreateUserInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CreateUserType extends AbstractType
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
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'new-password',
                    'data-password-visibility-target' => 'input',
                ],
                'label_attr' => [
                    'class' => 'form-label',
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Administrateur' => 'ROLE_ADMIN',
                    'Enseignant' => 'ROLE_TEACHER',
                    'Apprenant' => 'ROLE_STUDENT',
                    'Utilisateur' => 'ROLE_USER',
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
            'data_class' => CreateUserInput::class,
        ]);
    }
}
