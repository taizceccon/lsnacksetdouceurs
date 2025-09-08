<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    private ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('prix', MoneyType::class, [
                'label' => 'Prix (€)',
                'divisor' => 100,
                'currency' => 'EUR',
                'scale' => 2,
                'attr' => ['class' => 'border border-pink-300 rounded w-full p-2'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du produit (JPEG, PNG, GIF, WEBP)',
                'mapped' => false,
                'required' => false,
                // ATTENTION : dans le test, les contraintes ne sont pas validées
                // donc on les applique uniquement si Validator est activé
                'constraints' => class_exists(File::class) ? [
                    new File(maxSize: '2M', mimeTypes: [
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp',
                    ], mimeTypesMessage: 'Veuillez uploader une image valide (JPEG, PNG, GIF, WEBP)')
                ] : [],
                'attr' => ['class' => 'border border-pink-300 rounded w-full p-2'],
            ])
            ->add('urlvideo')
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'category',
                'label' => 'Catégorie',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}