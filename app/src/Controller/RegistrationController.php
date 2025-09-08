<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\User as AppUser;
use App\Entity\Order;
use App\Form\RegistrationForm;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Form\RegistrationFormType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ValidatorInterface $validator
    ): Response {
    
        if (
            $request->isMethod('POST') &&
            $request->request->has('email') &&
            !$request->request->has('registration_form')
        ) {
            $email = trim($request->request->get('email'));

            // Validation de l'email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Adresse e-mail invalide.');
                return $this->redirectToRoute('app_register');
            }

            // Rediriger vers /register?email=xxx pour préremplir
            return $this->redirectToRoute('app_register', ['email' => $email]);
        }

        $emailFromQuery = $request->query->get('email');
        $user = new User();

        // Ne préremplit que si ce n’est pas une soumission POST du formulaire principal
        if ($emailFromQuery && !$request->isMethod('POST')) {
            $user->setEmail($emailFromQuery);
        }


        $form = $this->createForm(RegistrationForm::class, $user, [
            'email_readonly' => !empty($emailFromQuery),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if ($form->isValid()) {
                // Vérifie si l'email est déjà utilisé
                $existingUser = $userRepository->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    $this->addFlash('error', 'Cette adresse e-mail est déjà utilisée.');
                    return $this->redirectToRoute('app_register');
                }

                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );

                $user->setIsVerified(true);

                $entityManager->persist($user);
                $entityManager->flush();

                $this->emailVerifier->sendEmailConfirmation(
                    'app_verify_email',
                    $user,
                    (new TemplatedEmail())
                        ->from(new Address('taizceccon@hotmail.fr', 'leila snacks et douceurs'))
                        ->to($user->getEmail())
                        ->subject('Veuillez confirmer votre adresse e-mail')
                        ->htmlTemplate('registration/confirmation_email.html.twig')
                );

                $this->addFlash('success', 'Votre compte a bien été créé. Vérifiez votre boîte mail pour confirmer votre adresse.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

   
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_login');
        }

        $this->addFlash('success', 'Your email address has been verified.');
        return $this->redirectToRoute('app_login');
    }

    #[Route('/mon-compte', name: 'app_user_dashboard')]
    public function userDashboard(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof AppUser) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les commandes de l'utilisateur
        $orders = $entityManager->getRepository(Order::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC']);

        // Formulaire avec option is_edit = true
        $form = $this->createForm(RegistrationForm::class, $user, [
            'email_readonly' => true,
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $currentPassword = $form->get('currentPassword')->getData();

            if (!empty($plainPassword)) {
                if (empty($currentPassword) || !$userPasswordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                    return $this->redirectToRoute('app_user_dashboard');
                }

                $hashedPassword = $userPasswordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        return $this->render('registration/user.html.twig', [
            'registrationForm' => $form->createView(),
            'orders' => $orders,
        ]);
    }


    // #[Route('/mon-compte', name: 'app_user_dashboard')]
    // public function userDashboard(
    //     Request $request,
    //     EntityManagerInterface $entityManager,
    //     UserPasswordHasherInterface $userPasswordHasher
    // ): Response {
    //     $user = $this->getUser();

    //     if (!$user instanceof AppUser) {
    //         return $this->redirectToRoute('app_login');
    //     }

    //     // Récupérer les commandes de l'utilisateur
    //     $orders = $entityManager->getRepository(Order::class)
    //         ->findBy(['user' => $user], ['createdAt' => 'DESC']);

    //     //Ajout de l'option email_readonly pour empêcher la modif
    //     $form = $this->createForm(RegistrationForm::class, $user, [
    //         'email_readonly' => true,
    //     ]);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $plainPassword = $form->get('plainPassword')->getData();
    //         $currentPassword = $form->get('currentPassword')->getData();

    //         //Vérifier si l’utilisateur veut changer son mot de passe
    //         if (!empty($plainPassword)) {
    //             // Vérification que le mot de passe actuel est correct
    //             if (empty($currentPassword) || !$userPasswordHasher->isPasswordValid($user, $currentPassword)) {
    //                 $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
    //                 return $this->redirectToRoute('app_user_dashboard');
    //             }

    //             //OK → on met à jour le mot de passe
    //             $hashedPassword = $userPasswordHasher->hashPassword($user, $plainPassword);
    //             $user->setPassword($hashedPassword);
    //         }

    //         $entityManager->flush();
    //         $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');

    //         return $this->redirectToRoute('app_user_dashboard');
    //     }

    //     return $this->render('registration/user.html.twig', [
    //         'registrationForm' => $form->createView(),
    //         'orders' => $orders,
    //     ]);
    // }
    
}