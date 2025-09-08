<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;


class RegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $emailReadonly = $options['email_readonly'] ?? false;
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('email', TextType::class, [
                'label' => 'Adresse e-mail',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une adresse e-mail']),
                    new Email(['message' => 'L\'adresse e-mail n\'est pas valide']),
                ],
                'attr' => [
                    'placeholder' => 'exemple@domaine.com',
                    'readonly' => $emailReadonly,
                    'class' => $emailReadonly ? 'readonly-email' : '',
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [
                    new NotBlank(['message' => 'Le nom est obligatoire']),
                    new Length(['max' => 100, 'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères']),
                ],
                'attr' => ['placeholder' => 'Votre nom'],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [
                    new NotBlank(['message' => 'L\'adresse est obligatoire']),
                    new Length(['max' => 255, 'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères']),
                ],
                'attr' => ['placeholder' => 'Adresse postale'],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'constraints' => [
                    new NotBlank(['message' => 'Le téléphone est obligatoire']),
                    new Regex([
                        'pattern' => '/^0[1-9](\s?\d{2}){4}$/',
                        'message' => 'Le numéro de téléphone est invalide.',
                    ]),
                ],
                'attr' => ['placeholder' => 'Ex: 01 23 45 67 89'],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte les conditions générales',
                'constraints' => [
                    new IsTrue(['message' => 'Vous devez accepter nos conditions.']),
                ],
            ]);

        if ($isEdit) {
            $builder
                ->add('currentPassword', PasswordType::class, [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'Mot de passe actuel',
                    'attr' => ['placeholder' => 'Mot de passe actuel'],
                ])
                ->add('plainPassword', PasswordType::class, [
                    'mapped' => false,
                    'required' => false,
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['placeholder' => 'Nouveau mot de passe'],
                    'constraints' => [
                        new Length(['min' => 6, 'max' => 4096]),
                    ],
                ]);
        } else {
            $builder
                ->add('plainPassword', PasswordType::class, [
                    'mapped' => false,
                    'required' => true,
                    'label' => 'Mot de passe',
                    'attr' => ['placeholder' => 'Mot de passe'],
                    'constraints' => [
                        new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                        new Length(['min' => 6, 'max' => 4096]),
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
      $resolver->setDefaults([
        'data_class' => User::class,
        'email_readonly' => false,
        'is_edit' => false,
        'csrf_protection' => true,
        'csrf_field_name' => '_token',
        'csrf_token_id'   => 'registration_form',
      ]);
    }
}

// class RegistrationForm extends AbstractType
// {
//     public function buildForm(FormBuilderInterface $builder, array $options): void
//     {
//         $emailReadonly = $options['email_readonly'] ?? false;

//         $builder
//             ->add('email', TextType::class, [
//                 'label' => 'Adresse e-mail',
//                 'constraints' => [
//                     new NotBlank(['message' => 'Veuillez entrer une adresse e-mail']),
//                     new Email(['message' => 'L\'adresse e-mail n\'est pas valide']),
//                 ],
//                 'attr' => [
//                     'placeholder' => 'exemple@domaine.com',
//                     'readonly' => $emailReadonly,
//                     'class' => $emailReadonly ? 'readonly-email' : '',
//                 ],
//             ])
//             ->add('nom', TextType::class, [
//                 'label' => 'Nom complet',
//                 'constraints' => [
//                     new NotBlank(['message' => 'Le nom est obligatoire']),
//                     new Length(['max' => 100, 'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères']),
//                 ],
//                 'attr' => ['placeholder' => 'Votre nom'],
//             ])
//             ->add('adresse', TextType::class, [
//                 'required' => true,
//                 'label' => 'Adresse',
//                 'constraints' => [
//                     new NotBlank(['message' => 'L\'adresse est obligatoire']),
//                     new Length(['max' => 255, 'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères']),
//                 ],
//                 'attr' => ['placeholder' => 'Adresse postale'],
//             ])
//             ->add('telephone', TextType::class, [
//                 'required' => true,
//                 'label' => 'Téléphone',
//                 'constraints' => [
//                     new NotBlank(['message' => 'Le téléphone est obligatoire']),
//                     new Regex([
//                         'pattern' => '/^0[1-9](\s?\d{2}){4}$/',
//                         'message' => 'Le numéro de téléphone est invalide.',
//                     ]),
//                 ],
//                 'attr' => ['placeholder' => 'Ex: 01 23 45 67 89'],
//             ])
//             ->add('currentPassword', PasswordType::class, [
//                 'mapped' => false,
//                 'required' => false, // obligatoire seulement si plainPassword est rempli
//                 'label' => 'Mot de passe actuel',
//                 'attr' => [
//                     'autocomplete' => 'current-password',
//                     'placeholder' => 'Mot de passe actuel',
//                 ],
//             ])
//             ->add('plainPassword', PasswordType::class, [
//                 'mapped' => false,
//                 'required' => false,
//                 'attr' => [
//                     'autocomplete' => 'new-password',
//                     'placeholder' => 'Nouveau mot de passe',
//                 ],
//                 'label' => 'Nouveau mot de passe',
//                 'constraints' => [
//                     new Length([
//                         'min' => 6,
//                         'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
//                         'max' => 4096,
//                     ]),
//                 ],
//             ])
//             ->add('agreeTerms', CheckboxType::class, [
//                 'mapped' => false,
//                 'label' => 'J\'accepte les conditions générales',
//                 'constraints' => [
//                     new IsTrue(['message' => 'Vous devez accepter nos conditions.']),
//                 ],
//             ]);
//     }

//     public function configureOptions(OptionsResolver $resolver): void
//     {
//         $resolver->setDefaults([
//             'data_class' => User::class,
//             'email_readonly' => false, // Option personnalisée
//         ]);
//     }


  
// }