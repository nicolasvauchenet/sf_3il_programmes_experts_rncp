<?php

namespace App\Form;

use App\Entity\PromotionDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, FileType, IntegerType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class PromotionDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $new = $options['data']->id === null;
        $constraints = [new Assert\File(maxSize: '20M', extensions: ['pdf', 'xlsx'])];
        if ($new) $constraints[] = new Assert\NotNull(message: 'Choisis un fichier.');
        $builder
            ->add('label', TextType::class, ['label' => 'Libellé', 'attr' => ['class' => 'form-control', 'maxlength' => 255]])
            ->add('position', IntegerType::class, ['label' => 'Ordre d’affichage', 'attr' => ['class' => 'form-control', 'min' => 0]])
            ->add('visible', CheckboxType::class, [
                'label' => 'Visible dans le Résumé',
                'required' => false,
                'row_attr' => ['class' => 'form-checkbox-row'],
            ])
            ->add('file', FileType::class, [
                'label' => $new ? 'Document' : 'Remplacer le fichier (facultatif)',
                'mapped' => false, 'required' => $new, 'constraints' => $constraints,
                'help' => 'PDF ou XLSX — 20 Mo maximum.',
                'attr' => ['class' => 'form-control', 'accept' => '.pdf,.xlsx'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PromotionDocument::class]);
    }
}
